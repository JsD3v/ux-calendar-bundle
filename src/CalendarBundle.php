<?php

namespace JeanSebastienChristophe\CalendarBundle;

use JeanSebastienChristophe\CalendarBundle\DependencyInjection\CalendarExtension as CalendarDIExtension;
use JeanSebastienChristophe\CalendarBundle\DependencyInjection\Compiler\RegisterAssetsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CalendarBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterAssetsCompilerPass());
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return $this->extension ??= new CalendarDIExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
