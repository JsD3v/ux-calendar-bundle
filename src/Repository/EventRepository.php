<?php

namespace JeanSebastienChristophe\CalendarBundle\Repository;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventRangeRepositoryInterface;
use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventRepositoryInterface;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository implements CalendarEventRepositoryInterface, CalendarEventRangeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Récupère tous les événements d'un mois donné
     *
     * @param int $year
     * @param int $month
     * @return Event[]
     */
    public function findByMonth(int $year, int $month): array
    {
        $startDate = new \DateTime(sprintf('%d-%02d-01 00:00:00', $year, $month));
        $endDate = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->findByDateRange($startDate, $endDate);
    }

    /**
     * Récupère les événements d'une journée spécifique
     *
     * @param \DateTimeInterface $date
     * @return Event[]
     */
    public function findByDay(\DateTimeInterface $date): array
    {
        $startOfDay = \DateTime::createFromInterface($date)->setTime(0, 0, 0);
        $endOfDay = \DateTime::createFromInterface($date)->setTime(23, 59, 59);

        return $this->findByDateRange($startOfDay, $endOfDay);
    }

    /**
     * Récupère tous les événements chevauchant la plage [start, end]
     *
     * @return Event[]
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.startDate BETWEEN :start AND :end')
            ->orWhere('e.endDate BETWEEN :start AND :end')
            ->orWhere('e.startDate <= :start AND e.endDate >= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->persist($event);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->remove($event);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
