<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Repository;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use JeanSebastienChristophe\CalendarBundle\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventRepositoryTest extends TestCase
{
    private EventRepository|MockObject $repository;
    private EntityManagerInterface|MockObject $entityManager;
    private ManagerRegistry|MockObject $registry;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry
            ->method('getManagerForClass')
            ->with(Event::class)
            ->willReturn($this->entityManager);

        // Create a partial mock of EventRepository to test save/remove methods
        $this->repository = $this->getMockBuilder(EventRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();

        $this->repository
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testSaveWithFlush(): void
    {
        $event = new Event();
        $event->setTitle('Test Event')
            ->setStartDate(new \DateTime('2025-01-15 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-15 12:00:00'));

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($event);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->save($event);
    }

    public function testSaveWithoutFlush(): void
    {
        $event = new Event();
        $event->setTitle('Test Event')
            ->setStartDate(new \DateTime('2025-01-15 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-15 12:00:00'));

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($event);

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->repository->save($event, false);
    }

    public function testRemoveWithFlush(): void
    {
        $event = new Event();
        $event->setTitle('Test Event')
            ->setStartDate(new \DateTime('2025-01-15 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-15 12:00:00'));

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($event);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->remove($event);
    }

    public function testRemoveWithoutFlush(): void
    {
        $event = new Event();
        $event->setTitle('Test Event')
            ->setStartDate(new \DateTime('2025-01-15 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-15 12:00:00'));

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($event);

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->repository->remove($event, false);
    }

    public function testFindByMonthCreatesCorrectQuery(): void
    {
        $expectedEvents = [
            $this->createEvent('Event 1', '2025-01-10 10:00:00', '2025-01-10 12:00:00'),
            $this->createEvent('Event 2', '2025-01-15 09:00:00', '2025-01-15 17:00:00'),
        ];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedEvents);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $result = $this->repository->findByMonth(2025, 1);

        $this->assertCount(2, $result);
        $this->assertSame($expectedEvents, $result);
    }

    public function testFindByDayCreatesCorrectQuery(): void
    {
        $date = new \DateTime('2025-01-15');
        $expectedEvents = [
            $this->createEvent('Event 1', '2025-01-15 10:00:00', '2025-01-15 12:00:00'),
        ];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedEvents);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $result = $this->repository->findByDay($date);

        $this->assertCount(1, $result);
        $this->assertSame($expectedEvents, $result);
    }

    public function testFindByMonthReturnsEmptyArrayWhenNoEvents(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->repository
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->repository->findByMonth(2025, 2);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByDayReturnsEmptyArrayWhenNoEvents(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->repository
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->repository->findByDay(new \DateTime('2025-02-20'));

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    private function createEvent(string $title, string $startDate, string $endDate): Event
    {
        $event = new Event();
        $event->setTitle($title)
            ->setStartDate(new \DateTime($startDate))
            ->setEndDate(new \DateTime($endDate));

        return $event;
    }
}
