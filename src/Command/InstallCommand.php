<?php

namespace JeanSebastienChristophe\CalendarBundle\Command;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ux-calendar:install',
    description: 'Configure the calendar event entity used by the bundle.'
)]
class InstallCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $configuredEventClass = Event::class
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'event-class',
            null,
            InputOption::VALUE_REQUIRED,
            'FQCN of the entity implementing CalendarEventInterface.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $eventClass = $input->getOption('event-class');

        if (!is_string($eventClass) || trim($eventClass) === '') {
            if (!$input->isInteractive()) {
                $io->error('Pass the event entity class with --event-class=App\\Entity\\Event.');

                return Command::FAILURE;
            }

            $eventClass = $io->ask(
                'Event entity class',
                $this->suggestedEventClass()
            );
        }

        $eventClass = ltrim(trim((string) $eventClass), '\\');

        try {
            $this->validateEventClass($eventClass);
            $configPath = $this->projectDir . '/config/packages/calendar.yaml';
            $this->writeCalendarConfig($configPath, $eventClass);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Calendar event entity configured: %s', $eventClass));

        return Command::SUCCESS;
    }

    private function suggestedEventClass(): string
    {
        if ($this->configuredEventClass === Event::class) {
            return 'App\\Entity\\Event';
        }

        return $this->configuredEventClass;
    }

    private function validateEventClass(string $eventClass): void
    {
        if (!class_exists($eventClass)) {
            throw new \InvalidArgumentException(sprintf('The event class "%s" does not exist.', $eventClass));
        }

        if (!is_subclass_of($eventClass, CalendarEventInterface::class)) {
            throw new \InvalidArgumentException(sprintf(
                'The event class "%s" must implement "%s".',
                $eventClass,
                CalendarEventInterface::class
            ));
        }

        $reflectionClass = new \ReflectionClass($eventClass);
        if (!$reflectionClass->isInstantiable()) {
            throw new \InvalidArgumentException(sprintf('The event class "%s" must be instantiable.', $eventClass));
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            throw new \InvalidArgumentException(sprintf(
                'The event class "%s" must have no required constructor arguments.',
                $eventClass
            ));
        }
    }

    private function writeCalendarConfig(string $configPath, string $eventClass): void
    {
        $directory = dirname($configPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create config directory "%s".', $directory));
        }

        $line = '    event_class: ' . $this->yamlString($eventClass);

        if (!is_file($configPath)) {
            $this->writeFile($configPath, "calendar:\n" . $line . "\n");

            return;
        }

        $contents = file_get_contents($configPath);
        if ($contents === false) {
            throw new \RuntimeException(sprintf('Unable to read config file "%s".', $configPath));
        }

        $updatedContents = $this->upsertEventClass($contents, $line);
        $this->writeFile($configPath, $updatedContents);
    }

    private function upsertEventClass(string $contents, string $eventClassLine): string
    {
        $lines = preg_split('/\R/', rtrim($contents, "\r\n"));
        if ($lines === false || $lines === ['']) {
            return "calendar:\n" . $eventClassLine . "\n";
        }

        $calendarLine = null;
        foreach ($lines as $index => $line) {
            if (preg_match('/^calendar:\s*(?:#.*)?$/', $line) === 1) {
                $calendarLine = $index;
                break;
            }
        }

        if ($calendarLine === null) {
            $lines[] = '';
            $lines[] = 'calendar:';
            $lines[] = $eventClassLine;

            return implode("\n", $lines) . "\n";
        }

        $insertAt = $calendarLine + 1;
        for ($index = $calendarLine + 1, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];

            if ($line !== '' && preg_match('/^\S/', $line) === 1) {
                break;
            }

            if (preg_match('/^(\s*)event_class\s*:/', $line, $matches) === 1) {
                $lines[$index] = $matches[1] . trim($eventClassLine);

                return implode("\n", $lines) . "\n";
            }

            $insertAt = $index + 1;
        }

        array_splice($lines, $insertAt, 0, [$eventClassLine]);

        return implode("\n", $lines) . "\n";
    }

    private function yamlString(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException(sprintf('Unable to write config file "%s".', $path));
        }
    }
}
