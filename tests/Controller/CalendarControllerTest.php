<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Controller;

use JeanSebastienChristophe\CalendarBundle\Controller\CalendarController;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CalendarControllerTest extends TestCase
{
    private CalendarController $controller;
    private EntityManagerInterface|MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->controller = new CalendarController($this->entityManager, Event::class);
    }

    public function testControllerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(CalendarController::class, $this->controller);
    }

    public function testBuildCalendarGridReturnsCorrectStructure(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        $events = [];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        $this->assertIsArray($result);
        // January 2025 starts on Wednesday (3rd day of week)
        // So we need 5 weeks to display all days
        $this->assertGreaterThanOrEqual(4, count($result));
        $this->assertLessThanOrEqual(6, count($result));
    }

    public function testBuildCalendarGridHasSevenDaysPerWeek(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        $events = [];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        foreach ($result as $week) {
            $this->assertCount(7, $week);
        }
    }

    public function testBuildCalendarGridMarksToday(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        $now = new \DateTime();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('m');
        $today = (int) $now->format('d');

        $events = [];
        $result = $method->invokeArgs($this->controller, [$year, $month, $events]);

        $foundToday = false;
        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null && $cell['day'] === $today) {
                    $this->assertTrue($cell['is_today']);
                    $foundToday = true;
                }
            }
        }

        $this->assertTrue($foundToday, 'Today should be marked in the calendar grid');
    }

    public function testBuildCalendarGridIncludesEvents(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        $event = new Event();
        $event->setTitle('Test Event')
            ->setStartDate(new \DateTime('2025-01-15 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-15 12:00:00'));

        $events = [$event];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        $foundEventInDay15 = false;
        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null && $cell['day'] === 15) {
                    $this->assertNotEmpty($cell['events']);
                    $this->assertSame($event, $cell['events'][0]);
                    $foundEventInDay15 = true;
                }
            }
        }

        $this->assertTrue($foundEventInDay15, 'Event should be included in day 15');
    }

    public function testBuildCalendarGridHandlesMultiDayEvent(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        $event = new Event();
        $event->setTitle('Multi-day Event')
            ->setStartDate(new \DateTime('2025-01-10 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-12 18:00:00'));

        $events = [$event];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        $daysWithEvent = [];
        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null && !empty($cell['events'])) {
                    $daysWithEvent[] = $cell['day'];
                }
            }
        }

        // Event should appear on days 10, 11, and 12
        $this->assertContains(10, $daysWithEvent);
        $this->assertContains(11, $daysWithEvent);
        $this->assertContains(12, $daysWithEvent);
    }

    public function testBuildCalendarGridHandlesFebruaryLeapYear(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        // 2024 is a leap year
        $events = [];
        $result = $method->invokeArgs($this->controller, [2024, 2, $events]);

        // Count total non-null cells
        $totalDays = 0;
        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null) {
                    $totalDays++;
                }
            }
        }

        $this->assertEquals(29, $totalDays, 'February 2024 should have 29 days');
    }

    public function testBuildCalendarGridHandlesFebruaryNonLeapYear(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        // 2025 is not a leap year
        $events = [];
        $result = $method->invokeArgs($this->controller, [2025, 2, $events]);

        // Count total non-null cells
        $totalDays = 0;
        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null) {
                    $totalDays++;
                }
            }
        }

        $this->assertEquals(28, $totalDays, 'February 2025 should have 28 days');
    }

    public function testBuildCalendarGridFirstDayIsCorrect(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        // January 2025 starts on Wednesday (index 2 in 0-based week starting Monday)
        $events = [];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        // First week should have empty cells before Wednesday
        $firstWeek = $result[0];

        // Monday and Tuesday should be null
        $this->assertNull($firstWeek[0]);
        $this->assertNull($firstWeek[1]);
        // Wednesday should be day 1
        $this->assertNotNull($firstWeek[2]);
        $this->assertEquals(1, $firstWeek[2]['day']);
    }

    public function testBuildCalendarGridLastDayIsCorrect(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        // January 2025 has 31 days
        $events = [];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        // Find day 31
        $foundDay31 = false;
        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null && $cell['day'] === 31) {
                    $foundDay31 = true;
                }
            }
        }

        $this->assertTrue($foundDay31, 'Day 31 should exist in January');
    }

    public function testBuildCalendarGridCellContainsDateObject(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        $events = [];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null) {
                    $this->assertArrayHasKey('date', $cell);
                    $this->assertInstanceOf(\DateTime::class, $cell['date']);
                    $this->assertArrayHasKey('day', $cell);
                    $this->assertArrayHasKey('events', $cell);
                    $this->assertArrayHasKey('is_today', $cell);
                }
            }
        }
    }

    public function testBuildCalendarGridEventsAreIndexedArray(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildCalendarGrid');
        $method->setAccessible(true);

        $event = new Event();
        $event->setTitle('Test Event')
            ->setStartDate(new \DateTime('2025-01-15 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-15 12:00:00'));

        $events = [$event];
        $result = $method->invokeArgs($this->controller, [2025, 1, $events]);

        foreach ($result as $week) {
            foreach ($week as $cell) {
                if ($cell !== null && $cell['day'] === 15) {
                    // Events should be a numerically indexed array (from array_values)
                    $this->assertArrayHasKey(0, $cell['events']);
                }
            }
        }
    }
}
