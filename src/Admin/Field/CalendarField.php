<?php

namespace JeanSebastienChristophe\CalendarBundle\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

/**
 * Custom EasyAdmin field that displays a mini calendar with events
 */
final class CalendarField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_SHOW_NAVIGATION = 'showNavigation';
    public const OPTION_SHOW_TODAY_BUTTON = 'showTodayButton';
    public const OPTION_DEFAULT_VIEW = 'defaultView';
    public const OPTION_MAX_EVENTS_PER_DAY = 'maxEventsPerDay';
    public const OPTION_CLICKABLE_EVENTS = 'clickableEvents';
    public const OPTION_CALENDAR_HEIGHT = 'calendarHeight';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label ?? 'Calendrier')
            ->setTemplatePath('@Calendar/admin/field/calendar.html.twig')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\HiddenType::class)
            ->addCssClass('field-calendar')
            ->setCustomOption(self::OPTION_SHOW_NAVIGATION, true)
            ->setCustomOption(self::OPTION_SHOW_TODAY_BUTTON, true)
            ->setCustomOption(self::OPTION_DEFAULT_VIEW, 'month')
            ->setCustomOption(self::OPTION_MAX_EVENTS_PER_DAY, 3)
            ->setCustomOption(self::OPTION_CLICKABLE_EVENTS, true)
            ->setCustomOption(self::OPTION_CALENDAR_HEIGHT, '500px');
    }

    public function showNavigation(bool $show = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_NAVIGATION, $show);
        return $this;
    }

    public function showTodayButton(bool $show = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_TODAY_BUTTON, $show);
        return $this;
    }

    public function setDefaultView(string $view): self
    {
        $this->setCustomOption(self::OPTION_DEFAULT_VIEW, $view);
        return $this;
    }

    public function setMaxEventsPerDay(int $max): self
    {
        $this->setCustomOption(self::OPTION_MAX_EVENTS_PER_DAY, $max);
        return $this;
    }

    public function setClickableEvents(bool $clickable = true): self
    {
        $this->setCustomOption(self::OPTION_CLICKABLE_EVENTS, $clickable);
        return $this;
    }

    public function setCalendarHeight(string $height): self
    {
        $this->setCustomOption(self::OPTION_CALENDAR_HEIGHT, $height);
        return $this;
    }
}
