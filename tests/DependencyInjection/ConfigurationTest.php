<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\DependencyInjection;

use JeanSebastienChristophe\CalendarBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private Configuration $configuration;
    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testDefaultConfiguration(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            []
        );

        $this->assertEquals('/events', $config['route_prefix']);
        $this->assertEquals(['month'], $config['views']['enabled']);
        $this->assertEquals('month', $config['views']['default']);
        $this->assertTrue($config['features']['all_day_events']);
        $this->assertTrue($config['features']['colors']);
    }

    public function testCustomRoutePrefix(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['route_prefix' => '/calendar']
            ]
        );

        $this->assertEquals('/calendar', $config['route_prefix']);
    }

    public function testCustomViewsEnabled(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['views' => ['enabled' => ['month', 'week', 'day']]]
            ]
        );

        $this->assertEquals(['month', 'week', 'day'], $config['views']['enabled']);
    }

    public function testCustomDefaultView(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['views' => ['default' => 'week']]
            ]
        );

        $this->assertEquals('week', $config['views']['default']);
    }

    public function testDisableAllDayEvents(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['features' => ['all_day_events' => false]]
            ]
        );

        $this->assertFalse($config['features']['all_day_events']);
    }

    public function testDisableColors(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['features' => ['colors' => false]]
            ]
        );

        $this->assertFalse($config['features']['colors']);
    }

    public function testCompleteCustomConfiguration(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                [
                    'route_prefix' => '/my-calendar',
                    'views' => [
                        'enabled' => ['month', 'week'],
                        'default' => 'week',
                    ],
                    'features' => [
                        'all_day_events' => true,
                        'colors' => false,
                    ],
                ]
            ]
        );

        $this->assertEquals('/my-calendar', $config['route_prefix']);
        $this->assertEquals(['month', 'week'], $config['views']['enabled']);
        $this->assertEquals('week', $config['views']['default']);
        $this->assertTrue($config['features']['all_day_events']);
        $this->assertFalse($config['features']['colors']);
    }

    public function testMultipleConfigurationArraysAreMerged(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['route_prefix' => '/events'],
                ['views' => ['default' => 'day']],
                ['features' => ['colors' => false]],
            ]
        );

        $this->assertEquals('/events', $config['route_prefix']);
        $this->assertEquals('day', $config['views']['default']);
        $this->assertFalse($config['features']['colors']);
    }

    public function testTreeBuilderHasCorrectRootName(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();

        $this->assertEquals('calendar', $treeBuilder->buildTree()->getName());
    }

    public function testEmptyViewsEnabledArray(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['views' => ['enabled' => []]]
            ]
        );

        $this->assertEmpty($config['views']['enabled']);
    }

    public function testSingleViewEnabled(): void
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                ['views' => ['enabled' => ['day']]]
            ]
        );

        $this->assertEquals(['day'], $config['views']['enabled']);
    }
}