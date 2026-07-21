<?php

namespace JeanSebastienChristophe\CalendarBundle;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController;
use JeanSebastienChristophe\CalendarBundle\DependencyInjection\CalendarExtension as CalendarDIExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CalendarBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return $this->extension ??= new CalendarDIExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * EasyAdmin resolves the CRUD entity through a static call, so the
     * configured event class has to be handed over once the container is
     * available rather than injected as a constructor argument.
     */
    public function boot(): void
    {
        if (!class_exists(AbstractCrudController::class) || null === $this->container) {
            return;
        }

        $eventClass = $this->container->getParameter('calendar.event_class');

        if (\is_string($eventClass)) {
            EventCrudController::setEntityFqcn($eventClass);
        }
    }
}
