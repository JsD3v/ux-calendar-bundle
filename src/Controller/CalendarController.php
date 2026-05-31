<?php

namespace JeanSebastienChristophe\CalendarBundle\Controller;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventRangeRepositoryInterface;
use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventRepositoryInterface;
use JeanSebastienChristophe\CalendarBundle\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('%calendar.route_prefix%')]
class CalendarController extends AbstractController
{
    /**
     * @param array{enabled: string[], default: string} $views
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $eventClass,
        private readonly array $views = ['enabled' => ['month'], 'default' => 'month'],
    ) {
    }

    #[Route('', name: 'calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        // Honour the configured default view (calendar.views.default).
        return $this->redirectToView($this->normalizeView($this->views['default'] ?? 'month'), new \DateTime('today'));
    }

    #[Route('/{year}/{month}', name: 'calendar_month', requirements: ['year' => '\d{4}', 'month' => '\d{2}'], methods: ['GET'])]
    public function month(string $year, string $month): Response
    {
        // The route enforces digits via requirements; cast here because a
        // zero-padded month like "05" is rejected by FILTER_VALIDATE_INT
        // when the argument is typed `int`.
        $year = (int) $year;
        $month = (int) $month;

        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException('Mois invalide');
        }

        return $this->render(
            '@Calendar/calendar/index.html.twig',
            $this->buildFrameContext('month', new \DateTime(sprintf('%d-%02d-01', $year, $month)))
        );
    }

    #[Route('/week/{date}', name: 'calendar_week', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['GET'])]
    public function week(string $date): Response
    {
        return $this->render(
            '@Calendar/calendar/index.html.twig',
            $this->buildFrameContext('week', $this->parseDate($date))
        );
    }

    #[Route('/day/{date}', name: 'calendar_day', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['GET'])]
    public function day(string $date): Response
    {
        return $this->render(
            '@Calendar/calendar/index.html.twig',
            $this->buildFrameContext('day', $this->parseDate($date))
        );
    }

    #[Route('/new', name: 'calendar_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $view = $this->normalizeView((string) $request->query->get('view', 'month'));

        // Créer une instance de l'entité configurée
        $event = new ($this->eventClass)();

        if (!$event instanceof CalendarEventInterface) {
            throw new \RuntimeException(
                'Event class must implement CalendarEventInterface'
            );
        }

        // Pré-remplir avec les paramètres de la requête (date/heure cliquée)
        if ($request->query->has('date')) {
            try {
                $date = new \DateTime($request->query->get('date'));
                $event->setStartDate($date);
                $event->setEndDate((clone $date)->modify('+1 hour'));
            } catch (\Exception $e) {
                // Date invalide, utiliser la date du jour
                $date = new \DateTime();
                $event->setStartDate($date);
                $event->setEndDate((clone $date)->modify('+1 hour'));
                $this->addFlash('warning', 'calendar.flash.invalid_date');
            }
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $eventDate = \DateTime::createFromInterface($event->getStartDate());

            // Si la requête vient de Turbo, on renvoie un Stream
            if ($this->isTurboStreamRequest($request)) {
                return $this->turboStream('@Calendar/calendar/stream/created.stream.html.twig', $view, $eventDate, [
                    'event' => $event,
                ]);
            }

            $this->addFlash('success', 'calendar.flash.created');
            return $this->redirectToView($view, $eventDate);
        }

        return $this->render('@Calendar/calendar/new.html.twig', [
            'event' => $event,
            'form' => $form,
            'view' => $view,
        ]);
    }

    #[Route('/{id}/edit', name: 'calendar_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CalendarEventInterface $event): Response
    {
        $view = $this->normalizeView((string) $request->query->get('view', 'month'));

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $eventDate = \DateTime::createFromInterface($event->getStartDate());

            // Si la requête vient de Turbo, on renvoie un Stream
            if ($this->isTurboStreamRequest($request)) {
                return $this->turboStream('@Calendar/calendar/stream/updated.stream.html.twig', $view, $eventDate, [
                    'event' => $event,
                ]);
            }

            $this->addFlash('success', 'calendar.flash.updated');
            return $this->redirectToView($view, $eventDate);
        }

        return $this->render('@Calendar/calendar/edit.html.twig', [
            'event' => $event,
            'form' => $form,
            'view' => $view,
        ]);
    }

    #[Route('/{id}/exclude/{date}', name: 'calendar_event_exclude_date', methods: ['POST'])]
    public function excludeDate(Request $request, CalendarEventInterface $event, string $date): Response
    {
        // Valider le token CSRF
        if (!$this->isCsrfTokenValid('exclude' . $event->getId() . $date, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        // Valider et parser la date
        try {
            $dateToExclude = new \DateTime($date);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('calendar.error.invalid_date');
        }

        // Exclure la date
        $event->excludeDate($dateToExclude);
        $this->entityManager->flush();

        // Si la requête vient de Turbo, on renvoie un Stream
        if ($this->isTurboStreamRequest($request)) {
            $response = $this->render('@Calendar/calendar/stream/date_excluded.stream.html.twig', [
                'eventId' => $event->getId(),
                'excludedDate' => $date,
            ]);
            $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');
            return $response;
        }

        $this->addFlash('success', 'calendar.flash.date_excluded');
        return $this->redirectToView(
            $this->normalizeView((string) $request->query->get('view', 'month')),
            $dateToExclude
        );
    }

    #[Route('/{id}', name: 'calendar_event_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, CalendarEventInterface $event): Response
    {
        // Support both DELETE and POST with _method=DELETE
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            // Sauvegarder les données avant suppression (car l'ID sera perdu après flush)
            $eventId = $event->getId();
            $eventDate = \DateTime::createFromInterface($event->getStartDate());

            $this->entityManager->remove($event);
            $this->entityManager->flush();

            // Si la requête vient de Turbo, on renvoie un Stream
            if ($this->isTurboStreamRequest($request)) {
                $response = $this->render('@Calendar/calendar/stream/deleted.stream.html.twig', [
                    'eventId' => $eventId,
                ]);
                $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');
                return $response;
            }

            $this->addFlash('success', 'calendar.flash.deleted');

            // Rediriger vers la vue de l'événement supprimé pour conserver le contexte
            return $this->redirectToView(
                $this->normalizeView((string) $request->query->get('view', 'month')),
                $eventDate
            );
        }

        return $this->redirectToRoute('calendar_index');
    }

    /**
     * Vérifie si la requête accepte les Turbo Streams
     */
    private function isTurboStreamRequest(Request $request): bool
    {
        return str_contains($request->headers->get('Accept', ''), 'text/vnd.turbo-stream.html');
    }

    /**
     * Rend un Turbo Stream qui rafraîchit la frame du calendrier pour la vue
     * et la date données (le template inclut `_calendar_frame.html.twig`).
     *
     * @param array<string, mixed> $extra
     */
    private function turboStream(string $template, string $view, \DateTime $date, array $extra = []): Response
    {
        $response = $this->render($template, $this->buildFrameContext($view, $date) + $extra);
        $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');

        return $response;
    }

    private function redirectToView(string $view, \DateTimeInterface $date): Response
    {
        return match ($view) {
            'week' => $this->redirectToRoute('calendar_week', ['date' => $date->format('Y-m-d')]),
            'day' => $this->redirectToRoute('calendar_day', ['date' => $date->format('Y-m-d')]),
            default => $this->redirectToRoute('calendar_month', [
                'year' => $date->format('Y'),
                'month' => $date->format('m'),
            ]),
        };
    }

    /**
     * Ramène une vue demandée à une vue activée, sinon la vue par défaut.
     */
    private function normalizeView(string $view): string
    {
        $enabled = $this->views['enabled'] ?? ['month'];

        if (\in_array($view, $enabled, true)) {
            return $view;
        }

        $default = $this->views['default'] ?? 'month';

        return \in_array($default, $enabled, true) ? $default : ($enabled[0] ?? 'month');
    }

    private function parseDate(string $date): \DateTime
    {
        // "!" resets the time part to 00:00:00.
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw $this->createNotFoundException('Date invalide');
        }

        return $parsed;
    }

    private function eventRepository(): CalendarEventRepositoryInterface
    {
        $repository = $this->entityManager->getRepository($this->eventClass);

        if (!$repository instanceof CalendarEventRepositoryInterface) {
            throw new \LogicException(sprintf(
                'The repository for event class "%s" must implement "%s".',
                $this->eventClass,
                CalendarEventRepositoryInterface::class
            ));
        }

        return $repository;
    }

    /**
     * Récupère les événements chevauchant [start, end].
     *
     * Utilise findByDateRange() si le repository implémente la capacité
     * optionnelle, sinon retombe sur findByMonth() pour chaque mois couvert
     * (les repositories custom n'implémentant que l'interface de base
     * continuent donc de fonctionner).
     *
     * @return CalendarEventInterface[]
     */
    private function findEventsBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $repository = $this->eventRepository();

        if ($repository instanceof CalendarEventRangeRepositoryInterface) {
            return $repository->findByDateRange($start, $end);
        }

        // Fallback : agréger les mois couverts puis dédupliquer par id.
        // buildDayColumns() filtre ensuite au jour près, donc les événements
        // hors plage récupérés par findByMonth() sont sans effet.
        $cursor = new \DateTime($start->format('Y-m-01'));
        $last = new \DateTime($end->format('Y-m-01'));
        $byId = [];

        while ($cursor <= $last) {
            foreach ($repository->findByMonth((int) $cursor->format('Y'), (int) $cursor->format('m')) as $event) {
                $byId[(int) $event->getId()] = $event;
            }
            $cursor->modify('+1 month');
        }

        return array_values($byId);
    }

    /**
     * Construit le jeu de variables partagé par toutes les vues et leur frame.
     *
     * @return array<string, mixed>
     */
    private function buildFrameContext(string $view, \DateTime $date): array
    {
        $view = $this->normalizeView($view);
        $enabled = $this->views['enabled'] ?? ['month'];

        $context = [
            'current_view' => $view,
            'current_date' => $date,
            'enabled_views' => $enabled,
            'nav' => $this->buildNav($view, $date),
            'view_links' => $this->buildViewLinks($date),
        ];

        switch ($view) {
            case 'week':
                $start = (clone $date)->modify('monday this week')->setTime(0, 0, 0);
                $end = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                $events = $this->findEventsBetween($start, $end);

                return $context + [
                    'week_start' => $start,
                    'week_end' => $end,
                    'day_columns' => $this->buildDayColumns($this->daysBetween($start, $end), $events),
                    'events' => $events,
                ];

            case 'day':
                $start = (clone $date)->setTime(0, 0, 0);
                $end = (clone $date)->setTime(23, 59, 59);
                $events = $this->findEventsBetween($start, $end);

                return $context + [
                    'day_columns' => $this->buildDayColumns([(clone $date)->setTime(0, 0, 0)], $events),
                    'events' => $events,
                ];

            default:
                $year = (int) $date->format('Y');
                $month = (int) $date->format('m');
                $events = $this->eventRepository()->findByMonth($year, $month);

                return $context + [
                    'year' => $year,
                    'month' => $month,
                    'calendar_data' => $this->buildCalendarGrid($year, $month, $events),
                    'events' => $events,
                ];
        }
    }

    /**
     * @return array{prev: string, next: string, today: string}
     */
    private function buildNav(string $view, \DateTime $date): array
    {
        $today = new \DateTime('today');

        return match ($view) {
            'week' => [
                'prev' => $this->generateUrl('calendar_week', ['date' => (clone $date)->modify('-7 days')->format('Y-m-d')]),
                'next' => $this->generateUrl('calendar_week', ['date' => (clone $date)->modify('+7 days')->format('Y-m-d')]),
                'today' => $this->generateUrl('calendar_week', ['date' => $today->format('Y-m-d')]),
            ],
            'day' => [
                'prev' => $this->generateUrl('calendar_day', ['date' => (clone $date)->modify('-1 day')->format('Y-m-d')]),
                'next' => $this->generateUrl('calendar_day', ['date' => (clone $date)->modify('+1 day')->format('Y-m-d')]),
                'today' => $this->generateUrl('calendar_day', ['date' => $today->format('Y-m-d')]),
            ],
            default => [
                'prev' => $this->generateUrl('calendar_month', $this->yearMonth((clone $date)->modify('first day of -1 month'))),
                'next' => $this->generateUrl('calendar_month', $this->yearMonth((clone $date)->modify('first day of +1 month'))),
                'today' => $this->generateUrl('calendar_month', $this->yearMonth($today)),
            ],
        };
    }

    /**
     * @return array<string, string> view name => URL centred on $date
     */
    private function buildViewLinks(\DateTime $date): array
    {
        return [
            'month' => $this->generateUrl('calendar_month', $this->yearMonth($date)),
            'week' => $this->generateUrl('calendar_week', ['date' => $date->format('Y-m-d')]),
            'day' => $this->generateUrl('calendar_day', ['date' => $date->format('Y-m-d')]),
        ];
    }

    /**
     * @return array{year: string, month: string}
     */
    private function yearMonth(\DateTimeInterface $date): array
    {
        return ['year' => $date->format('Y'), 'month' => $date->format('m')];
    }

    /**
     * @return \DateTime[] one midnight DateTime per day in [start, end]
     */
    private function daysBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $days = [];
        $cursor = (clone \DateTime::createFromInterface($start))->setTime(0, 0, 0);
        $last = (clone \DateTime::createFromInterface($end))->setTime(0, 0, 0);

        while ($cursor <= $last) {
            $days[] = clone $cursor;
            $cursor->modify('+1 day');
        }

        return $days;
    }

    /**
     * Construit les colonnes (jours) des vues semaine/jour, avec les événements
     * répartis en "toute la journée" et par heure de début.
     *
     * @param \DateTime[] $days
     * @param CalendarEventInterface[] $events
     * @return array<int, array{date: \DateTime, is_today: bool, all_day: CalendarEventInterface[], hours: array<int, CalendarEventInterface[]>}>
     */
    private function buildDayColumns(array $days, array $events): array
    {
        $today = (new \DateTime())->format('Y-m-d');
        $columns = [];

        foreach ($days as $day) {
            $dayStart = (clone $day)->setTime(0, 0, 0);
            $dayEnd = (clone $day)->setTime(23, 59, 59);

            $allDay = [];
            $hours = array_fill(0, 24, []);

            foreach ($events as $event) {
                $start = $event->getStartDate();
                $end = $event->getEndDate();

                // L'événement chevauche-t-il ce jour ?
                if ($end < $dayStart || $start > $dayEnd) {
                    continue;
                }

                if ($event->isAllDay()) {
                    $allDay[] = $event;
                    continue;
                }

                // Si l'événement a commencé un jour précédent, on l'ancre à 0h.
                $hour = $start < $dayStart ? 0 : (int) $start->format('G');
                $hours[$hour][] = $event;
            }

            $columns[] = [
                'date' => $day,
                'is_today' => $day->format('Y-m-d') === $today,
                'all_day' => $allDay,
                'hours' => $hours,
            ];
        }

        return $columns;
    }

    /**
     * Construit la grille du calendrier mensuel avec les événements
     *
     * @return array<int, array<int, array{date: \DateTime, day: int, events: CalendarEventInterface[], is_today: bool}|null>>
     */
    private function buildCalendarGrid(int $year, int $month, array $events): array
    {
        $firstDay = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $lastDay = (clone $firstDay)->modify('last day of this month');

        // Jour de la semaine du premier jour (1=lundi, 7=dimanche)
        $startDayOfWeek = (int) $firstDay->format('N');

        // Nombre de jours dans le mois
        $daysInMonth = (int) $lastDay->format('d');

        // Créer la grille
        $grid = [];
        $currentDay = 1;

        // Calculer le nombre de semaines à afficher
        $totalCells = $startDayOfWeek - 1 + $daysInMonth;
        $weeks = (int) ceil($totalCells / 7);

        for ($week = 0; $week < $weeks; $week++) {
            $grid[$week] = [];

            for ($day = 0; $day < 7; $day++) {
                $cellPosition = $week * 7 + $day;

                if ($cellPosition < $startDayOfWeek - 1 || $currentDay > $daysInMonth) {
                    // Cellule vide
                    $grid[$week][$day] = null;
                } else {
                    // Créer la date
                    $date = new \DateTime(sprintf('%d-%02d-%02d', $year, $month, $currentDay));

                    // Récupérer les événements de ce jour
                    $dayEvents = array_filter($events, function (CalendarEventInterface $event) use ($date) {
                        $eventStart = $event->getStartDate();
                        $eventEnd = $event->getEndDate();

                        return $eventStart->format('Y-m-d') === $date->format('Y-m-d')
                            || $eventEnd->format('Y-m-d') === $date->format('Y-m-d')
                            || ($eventStart < $date && $eventEnd > $date);
                    });

                    $grid[$week][$day] = [
                        'date' => $date,
                        'day' => $currentDay,
                        'events' => array_values($dayEvents),
                        'is_today' => $date->format('Y-m-d') === (new \DateTime())->format('Y-m-d'),
                    ];

                    $currentDay++;
                }
            }
        }

        return $grid;
    }
}
