<?php

namespace JeanSebastienChristophe\CalendarBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterAssetsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('asset_mapper.paths')) {
            return;
        }

        $paths = $container->getParameter('asset_mapper.paths');

        // Add CalendarBundle assets path
        $bundleAssetsPath = dirname(__DIR__, 2) . '/assets';
        $paths[$bundleAssetsPath] = '@calendar-bundle';

        $container->setParameter('asset_mapper.paths', $paths);
    }
}
