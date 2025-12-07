<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Entity;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private Event $event;

    protected function setUp(): void
    {
        $this->event = new Event();
    }

    public function testInitialValues(): void
    {
        $this->assertNull($this->event->getId());
        $this->assertNull($this->event->getTitle());
        $this->assertNull($this->event->getStartDate());
        $this->assertNull($this->event->getEndDate());
        $this->assertFalse($this->event->isAllDay());
        $this->assertNull($this->event->getDescription());
        $this->assertEquals('#3788d8', $this->event->getColor());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->event->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->event->getUpdatedAt());
    }

    public function testSetAndGetTitle(): void
    {
        $title = 'Test Event';
        $result = $this->event->setTitle($title);

        $this->assertSame($this->event, $result);
        $this->assertEquals($title, $this->event->getTitle());
    }

    public function testSetAndGetStartDate(): void
    {
        $date = new \DateTime('2025-01-15 10:00:00');
        $result = $this->event->setStartDate($date);

        $this->assertSame($this->event, $result);
        $this->assertEquals($date, $this->event->getStartDate());
    }

    public function testSetAndGetEndDate(): void
    {
        $date = new \DateTime('2025-01-15 12:00:00');
        $result = $this->event->setEndDate($date);

        $this->assertSame($this->event, $result);
        $this->assertEquals($date, $this->event->getEndDate());
    }

    public function testSetAndGetAllDay(): void
    {
        $result = $this->event->setAllDay(true);

        $this->assertSame($this->event, $result);
        $this->assertTrue($this->event->isAllDay());
    }

    public function testSetAndGetDescription(): void
    {
        $description = 'This is a test event description';
        $result = $this->event->setDescription($description);

        $this->assertSame($this->event, $result);
        $this->assertEquals($description, $this->event->getDescription());
    }

    public function testSetAndGetColor(): void
    {
        $color = '#FF5733';
        $result = $this->event->setColor($color);

        $this->assertSame($this->event, $result);
        $this->assertEquals($color, $this->event->getColor());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $date = new \DateTime('2025-01-01 00:00:00');
        $result = $this->event->setCreatedAt($date);

        $this->assertSame($this->event, $result);
        $this->assertEquals($date, $this->event->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $date = new \DateTime('2025-01-02 00:00:00');
        $result = $this->event->setUpdatedAt($date);

        $this->assertSame($this->event, $result);
        $this->assertEquals($date, $this->event->getUpdatedAt());
    }

    public function testFluentInterface(): void
    {
        $startDate = new \DateTime('2025-01-15 10:00:00');
        $endDate = new \DateTime('2025-01-15 12:00:00');

        $result = $this->event
            ->setTitle('Meeting')
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setAllDay(false)
            ->setDescription('Important meeting')
            ->setColor('#00FF00');

        $this->assertSame($this->event, $result);
        $this->assertEquals('Meeting', $this->event->getTitle());
        $this->assertEquals($startDate, $this->event->getStartDate());
        $this->assertEquals($endDate, $this->event->getEndDate());
        $this->assertFalse($this->event->isAllDay());
        $this->assertEquals('Important meeting', $this->event->getDescription());
        $this->assertEquals('#00FF00', $this->event->getColor());
    }

    public function testNullableDescription(): void
    {
        $this->event->setDescription('Some description');
        $this->event->setDescription(null);

        $this->assertNull($this->event->getDescription());
    }

    public function testNullableColor(): void
    {
        $this->event->setColor(null);

        $this->assertNull($this->event->getColor());
    }

    public function testCreatedAtSetOnConstruction(): void
    {
        $event = new Event();
        $now = new \DateTime();

        $this->assertInstanceOf(\DateTimeInterface::class, $event->getCreatedAt());
        // Check that createdAt is within 1 second of now
        $diff = $now->getTimestamp() - $event->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(1, abs($diff));
    }

    public function testUpdatedAtSetOnConstruction(): void
    {
        $event = new Event();
        $now = new \DateTime();

        $this->assertInstanceOf(\DateTimeInterface::class, $event->getUpdatedAt());
        // Check that updatedAt is within 1 second of now
        $diff = $now->getTimestamp() - $event->getUpdatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(1, abs($diff));
    }

    public function testSetUpdatedAtValue(): void
    {
        $oldUpdatedAt = $this->event->getUpdatedAt();
        sleep(1); // Ensure time difference

        $this->event->setUpdatedAtValue();

        $newUpdatedAt = $this->event->getUpdatedAt();
        $this->assertGreaterThan($oldUpdatedAt, $newUpdatedAt);
    }

    public function testAllDayEventDates(): void
    {
        $startDate = new \DateTime('2025-01-15 00:00:00');
        $endDate = new \DateTime('2025-01-15 23:59:59');

        $this->event
            ->setTitle('All Day Event')
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setAllDay(true);

        $this->assertTrue($this->event->isAllDay());
        $this->assertEquals('2025-01-15', $this->event->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2025-01-15', $this->event->getEndDate()->format('Y-m-d'));
    }

    public function testMultiDayEvent(): void
    {
        $startDate = new \DateTime('2025-01-15 10:00:00');
        $endDate = new \DateTime('2025-01-17 18:00:00');

        $this->event
            ->setTitle('Conference')
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $this->assertEquals('2025-01-15', $this->event->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2025-01-17', $this->event->getEndDate()->format('Y-m-d'));
    }
}