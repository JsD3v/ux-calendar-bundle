<?php

namespace JeanSebastienChristophe\CalendarBundle;

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
}
