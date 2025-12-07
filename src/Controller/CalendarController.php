<?php

namespace JeanSebastienChristophe\CalendarBundle\Controller;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('%calendar.route_prefix%')]
class CalendarController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $eventClass
    ) {
    }

    #[Route('', name: 'calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        $now = new \DateTime();
        return $this->redirectToRoute('calendar_month', [
            'year' => $now->format('Y'),
            'month' => $now->format('m'),
        ]);
    }

    #[Route('/{year}/{month}', name: 'calendar_month', requirements: ['year' => '\d{4}', 'month' => '\d{2}'], methods: ['GET'])]
    public function month(int $year, int $month): Response
    {
        // Validation du mois
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException('Mois invalide');
        }

        $currentDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));

        // Récupérer les événements du mois
        $repository = $this->entityManager->getRepository($this->eventClass);
        $events = $repository->findByMonth($year, $month);

        // Calcul des informations du calendrier
        $calendarData = $this->buildCalendarGrid($year, $month, $events);

        return $this->render('@Calendar/calendar/index.html.twig', [
            'current_date' => $currentDate,
            'year' => $year,
            'month' => $month,
            'calendar_data' => $calendarData,
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'calendar_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Créer une instance de l'entité configurée
        $event = new ($this->eventClass)();

        if (!$event instanceof CalendarEventInterface) {
            throw new \RuntimeException(
                'Event class must implement CalendarEventInterface'
            );
        }

        // Pré-remplir avec les paramètres de la requête (date cliquée)
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
                $this->addFlash('warning', 'La date fournie est invalide, utilisation de la date du jour.');
            }
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            // Si la requête vient de Turbo, on renvoie un Stream
            if ($this->isTurboStreamRequest($request)) {
                $year = (int) $event->getStartDate()->format('Y');
                $month = (int) $event->getStartDate()->format('m');

                // Recharger les données du calendrier
                $currentDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
                $repository = $this->entityManager->getRepository($this->eventClass);
                $events = $repository->findByMonth($year, $month);
                $calendarData = $this->buildCalendarGrid($year, $month, $events);

                $response = $this->render('@Calendar/calendar/stream/created.stream.html.twig', [
                    'event' => $event,
                    'year' => $year,
                    'month' => $month,
                    'current_date' => $currentDate,
                    'calendar_data' => $calendarData,
                    'events' => $events,
                ]);
                $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');
                return $response;
            }

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('calendar_month', [
                'year' => $event->getStartDate()->format('Y'),
                'month' => $event->getStartDate()->format('m'),
            ]);
        }

        return $this->render('@Calendar/calendar/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'calendar_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CalendarEventInterface $event): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Si la requête vient de Turbo, on renvoie un Stream
            if ($this->isTurboStreamRequest($request)) {
                $year = (int) $event->getStartDate()->format('Y');
                $month = (int) $event->getStartDate()->format('m');

                // Recharger les données du calendrier
                $currentDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
                $repository = $this->entityManager->getRepository($this->eventClass);
                $events = $repository->findByMonth($year, $month);
                $calendarData = $this->buildCalendarGrid($year, $month, $events);

                $response = $this->render('@Calendar/calendar/stream/updated.stream.html.twig', [
                    'event' => $event,
                    'year' => $year,
                    'month' => $month,
                    'current_date' => $currentDate,
                    'calendar_data' => $calendarData,
                    'events' => $events,
                ]);
                $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');
                return $response;
            }

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('calendar_month', [
                'year' => $event->getStartDate()->format('Y'),
                'month' => $event->getStartDate()->format('m'),
            ]);
        }

        return $this->render('@Calendar/calendar/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'calendar_event_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, CalendarEventInterface $event): Response
    {
        // Support both DELETE and POST with _method=DELETE
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            // Sauvegarder les données avant suppression (car l'ID sera perdu après flush)
            $eventId = $event->getId();
            $eventYear = $event->getStartDate()->format('Y');
            $eventMonth = $event->getStartDate()->format('m');

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

            $this->addFlash('success', 'Événement supprimé avec succès !');

            // Rediriger vers le mois de l'événement supprimé pour conserver le contexte
            return $this->redirectToRoute('calendar_month', [
                'year' => $eventYear,
                'month' => $eventMonth,
            ]);
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
     * Construit la grille du calendrier avec les événements
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
