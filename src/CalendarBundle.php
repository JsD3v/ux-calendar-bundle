<?php

namespace JeanSebastienChristophe\CalendarBundle;

use JeanSebastienChristophe\CalendarBundle\DependencyInjection\CalendarExtension as CalendarDIExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CalendarBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$builder->hasDefinition('asset_mapper')) {
            return;
        }

        // Register bundle assets with AssetMapper
        $container->extension('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets' => '@calendar-bundle',
                ],
            ],
        ]);
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
