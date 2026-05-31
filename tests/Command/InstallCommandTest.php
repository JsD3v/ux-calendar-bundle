<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Command;

use JeanSebastienChristophe\CalendarBundle\Command\InstallCommand;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use JeanSebastienChristophe\CalendarBundle\Tests\Fixtures\CustomEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class InstallCommandTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/ux_calendar_bundle_' . bin2hex(random_bytes(6));
        mkdir($this->projectDir . '/config/packages', 0775, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDir);
    }

    public function testCommandCreatesCalendarConfigWithEventClass(): void
    {
        $tester = new CommandTester(new InstallCommand($this->projectDir, Event::class));

        $statusCode = $tester->execute([
            '--event-class' => CustomEvent::class,
        ]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertStringContainsString(
            "event_class: '" . CustomEvent::class . "'",
            file_get_contents($this->projectDir . '/config/packages/calendar.yaml')
        );
    }

    public function testCommandAddsEventClassToExistingCalendarConfig(): void
    {
        file_put_contents(
            $this->projectDir . '/config/packages/calendar.yaml',
            "calendar:\n    route_prefix: /calendar\n"
        );

        $tester = new CommandTester(new InstallCommand($this->projectDir, Event::class));

        $statusCode = $tester->execute([
            '--event-class' => CustomEvent::class,
        ]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertSame(
            "calendar:\n    route_prefix: /calendar\n    event_class: '" . CustomEvent::class . "'\n",
            file_get_contents($this->projectDir . '/config/packages/calendar.yaml')
        );
    }

    public function testCommandUpdatesExistingEventClass(): void
    {
        file_put_contents(
            $this->projectDir . '/config/packages/calendar.yaml',
            "calendar:\n    event_class: 'Old\\Event'\n    route_prefix: /calendar\n"
        );

        $tester = new CommandTester(new InstallCommand($this->projectDir, Event::class));

        $statusCode = $tester->execute([
            '--event-class' => CustomEvent::class,
        ]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertSame(
            "calendar:\n    event_class: '" . CustomEvent::class . "'\n    route_prefix: /calendar\n",
            file_get_contents($this->projectDir . '/config/packages/calendar.yaml')
        );
    }

    public function testCommandRejectsUnknownEventClass(): void
    {
        $tester = new CommandTester(new InstallCommand($this->projectDir, Event::class));

        $statusCode = $tester->execute([
            '--event-class' => 'App\\Entity\\MissingEvent',
        ]);

        $this->assertSame(Command::FAILURE, $statusCode);
        $this->assertStringContainsString(
            'The event class "App\\Entity\\MissingEvent" does not exist.',
            $tester->getDisplay()
        );
        $this->assertFileDoesNotExist($this->projectDir . '/config/packages/calendar.yaml');
    }
}
