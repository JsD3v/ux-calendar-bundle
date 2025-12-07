<?php

namespace JeanSebastienChristophe\CalendarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('calendar');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('event_class')
                    ->defaultValue('JeanSebastienChristophe\CalendarBundle\Entity\Event')
                    ->info('FQCN of the entity implementing CalendarEventInterface')
                ->end()
                ->scalarNode('route_prefix')
                    ->defaultValue('/events')
                    ->info('Prefix for calendar routes')
                ->end()
                ->scalarNode('theme')
                    ->defaultValue('auto')
                    ->info('Theme to use: auto, bootstrap, tailwind, default')
                ->end()
                ->arrayNode('views')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('enabled')
                            ->defaultValue(['month'])
                            ->scalarPrototype()->end()
                            ->info('Enabled calendar views (month, week, day)')
                        ->end()
                        ->scalarNode('default')
                            ->defaultValue('month')
                            ->info('Default calendar view')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('features')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('all_day_events')
                            ->defaultTrue()
                            ->info('Enable all-day events')
                        ->end()
                        ->booleanNode('colors')
                            ->defaultTrue()
                            ->info('Enable event colors')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
