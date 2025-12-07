<?php

namespace JeanSebastienChristophe\CalendarBundle\Admin;

use JeanSebastienChristophe\CalendarBundle\Repository\EventRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Dashboard widget that provides calendar statistics and mini calendar view
 */
class CalendarDashboardWidget
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ?ChartBuilderInterface $chartBuilder = null
    ) {
    }

    /**
     * Get statistics for the dashboard
     */
    public function getStatistics(): array
    {
        $now = new \DateTime();
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        return [
            'total_events' => $this->eventRepository->count([]),
            'events_this_month' => $this->countEventsInRange($startOfMonth, $endOfMonth),
            'upcoming_events' => $this->countUpcomingEvents(),
            'events_today' => count($this->eventRepository->findByDay($now)),
        ];
    }

    /**
     * Get upcoming events for the widget
     */
    public function getUpcomingEvents(int $limit = 5): array
    {
        return $this->eventRepository->createQueryBuilder('e')
            ->where('e.startDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent events
     */
    public function getRecentEvents(int $limit = 5): array
    {
        return $this->eventRepository->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get events grouped by day for the current month
     */
    public function getMonthlyCalendarData(?int $year = null, ?int $month = null): array
    {
        $year = $year ?? (int) date('Y');
        $month = $month ?? (int) date('m');

        $events = $this->eventRepository->findByMonth($year, $month);

        return $this->buildCalendarGrid($year, $month, $events);
    }

    /**
     * Build a chart showing events per month (if ChartJS is available)
     */
    public function getEventsPerMonthChart(): ?Chart
    {
        if (!$this->chartBuilder) {
            return null;
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $monthlyData = $this->getEventsPerMonthData();

        $chart->setData([
            'labels' => array_keys($monthlyData),
            'datasets' => [
                [
                    'label' => 'Événements',
                    'backgroundColor' => '#3788d8',
                    'borderColor' => '#2563eb',
                    'data' => array_values($monthlyData),
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ]);

        return $chart;
    }

    /**
     * Get events count per month for the last 6 months
     */
    private function getEventsPerMonthData(): array
    {
        $data = [];
        $months = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');
            $label = $months[$month] . ' ' . $date->format('y');

            $count = count($this->eventRepository->findByMonth($year, $month));
            $data[$label] = $count;
        }

        return $data;
    }

    private function countEventsInRange(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return (int) $this->eventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.startDate BETWEEN :start AND :end')
            ->orWhere('e.endDate BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countUpcomingEvents(): int
    {
        return (int) $this->eventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildCalendarGrid(int $year, int $month, array $events): array
    {
        $firstDay = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $lastDay = (clone $firstDay)->modify('last day of this month');

        $startDayOfWeek = (int) $firstDay->format('N');
        $daysInMonth = (int) $lastDay->format('d');

        $grid = [];
        $currentDay = 1;
        $totalCells = $startDayOfWeek - 1 + $daysInMonth;
        $weeks = (int) ceil($totalCells / 7);

        for ($week = 0; $week < $weeks; $week++) {
            $grid[$week] = [];

            for ($day = 0; $day < 7; $day++) {
                $cellPosition = $week * 7 + $day;

                if ($cellPosition < $startDayOfWeek - 1 || $currentDay > $daysInMonth) {
                    $grid[$week][$day] = null;
                } else {
                    $date = new \DateTime(sprintf('%d-%02d-%02d', $year, $month, $currentDay));

                    $dayEvents = array_filter($events, function ($event) use ($date) {
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
