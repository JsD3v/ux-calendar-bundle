<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\DependencyInjection;

use JeanSebastienChristophe\CalendarBundle\DependencyInjection\CalendarExtension;
use JeanSebastienChristophe\CalendarBundle\Tests\Fixtures\CustomEvent;
use JeanSebastienChristophe\CalendarBundle\Tests\Fixtures\EventWithRequiredConstructor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CalendarExtensionTest extends TestCase
{
    private CalendarExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new CalendarExtension();
        $this->container = new ContainerBuilder();

        // Set up minimal required parameters
        $this->container->setParameter('kernel.bundles', [
            'TurboBundle' => 'Symfony\UX\Turbo\TurboBundle',
            'StimulusBundle' => 'Symfony\UX\StimulusBundle\StimulusBundle',
        ]);
    }

    public function testExtensionAlias(): void
    {
        $this->assertEquals('calendar', $this->extension->getAlias());
    }

    public function testLoadSetsDefaultRoutePrefix(): void
    {
        // Skip this test if AssetMapper is not available
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->extension->load([], $this->container);

        $this->assertTrue($this->container->hasParameter('calendar.route_prefix'));
        $this->assertEquals('/events', $this->container->getParameter('calendar.route_prefix'));
    }

    public function testLoadSetsCustomRoutePrefix(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->extension->load([
            ['route_prefix' => '/calendar']
        ], $this->container);

        $this->assertEquals('/calendar', $this->container->getParameter('calendar.route_prefix'));
    }

    public function testLoadSetsCustomEventClass(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->extension->load([
            ['event_class' => CustomEvent::class]
        ], $this->container);

        $this->assertEquals(CustomEvent::class, $this->container->getParameter('calendar.event_class'));
    }

    public function testLoadSetsThemeAndAssetsParameters(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->extension->load([
            [
                'theme' => 'bootstrap',
                'assets' => [
                    'include_cdn' => true,
                ],
            ]
        ], $this->container);

        $this->assertEquals('bootstrap', $this->container->getParameter('calendar.theme'));
        $this->assertEquals(['include_cdn' => true], $this->container->getParameter('calendar.assets'));
        $this->assertTrue($this->container->getParameter('calendar.assets.include_cdn'));
    }

    public function testLoadRejectsEventClassWithoutInterface(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $this->extension->load([
            ['event_class' => \stdClass::class]
        ], $this->container);
    }

    public function testLoadRejectsEventClassWithRequiredConstructorArguments(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have no required constructor arguments');

        $this->extension->load([
            ['event_class' => EventWithRequiredConstructor::class]
        ], $this->container);
    }

    public function testLoadSetsViewsParameter(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->extension->load([], $this->container);

        $this->assertTrue($this->container->hasParameter('calendar.views'));
        $views = $this->container->getParameter('calendar.views');
        $this->assertIsArray($views);
        $this->assertArrayHasKey('enabled', $views);
        $this->assertArrayHasKey('default', $views);
    }

    public function testLoadSetsFeaturesParameter(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $this->extension->load([], $this->container);

        $this->assertTrue($this->container->hasParameter('calendar.features'));
        $features = $this->container->getParameter('calendar.features');
        $this->assertIsArray($features);
        $this->assertArrayHasKey('all_day_events', $features);
        $this->assertArrayHasKey('colors', $features);
    }

    public function testLoadWithCustomConfiguration(): void
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            $this->markTestSkipped('AssetMapper not available');
        }

        $config = [
            [
                'route_prefix' => '/my-calendar',
                'views' => [
                    'enabled' => ['month', 'week'],
                    'default' => 'week',
                ],
                'features' => [
                    'all_day_events' => false,
                    'colors' => true,
                ],
            ]
        ];

        $this->extension->load($config, $this->container);

        $this->assertEquals('/my-calendar', $this->container->getParameter('calendar.route_prefix'));

        $views = $this->container->getParameter('calendar.views');
        $this->assertEquals(['month', 'week'], $views['enabled']);
        $this->assertEquals('week', $views['default']);

        $features = $this->container->getParameter('calendar.features');
        $this->assertFalse($features['all_day_events']);
        $this->assertTrue($features['colors']);
    }

    public function testMissingTurboBundleThrowsException(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', [
            'StimulusBundle' => 'Symfony\UX\StimulusBundle\StimulusBundle',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Turbo Bundle/');

        $this->extension->load([], $container);
    }

    public function testMissingStimulusBundleThrowsException(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', [
            'TurboBundle' => 'Symfony\UX\Turbo\TurboBundle',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Stimulus Bundle/');

        $this->extension->load([], $container);
    }

    public function testMissingBothBundlesThrowsException(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Turbo Bundle/');

        $this->extension->load([], $container);
    }

    public function testGetBundleNamePrivateMethod(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('getBundleName');

        $result = $method->invokeArgs($this->extension, ['Symfony\UX\Turbo\TurboBundle']);
        $this->assertEquals('TurboBundle', $result);

        $result = $method->invokeArgs($this->extension, ['JeanSebastienChristophe\CalendarBundle\CalendarBundle']);
        $this->assertEquals('CalendarBundle', $result);
    }

    public function testThrowMissingBundlesExceptionIncludesInstallCommands(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('throwMissingBundlesException');

        $missingBundles = [
            [
                'name' => 'Test Bundle',
                'package' => 'test/bundle',
                'command' => 'composer require test/bundle',
            ],
        ];

        try {
            $method->invokeArgs($this->extension, [$missingBundles]);
            $this->fail('Expected LogicException');
        } catch (\LogicException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Test Bundle', $message);
            $this->assertStringContainsString('test/bundle', $message);
            $this->assertStringContainsString('composer require test/bundle', $message);
            $this->assertStringContainsString('cache:clear', $message);
            $this->assertStringContainsString('importmap:install', $message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function prependWith(array $bundles, ?string $eventClass): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        $container->registerExtension($this->extension);
        $container->loadFromExtension('calendar', $eventClass === null ? [] : ['event_class' => $eventClass]);

        $this->extension->prepend($container);

        return $container->getExtensionConfig('doctrine');
    }

    public function testPrependRegistersDoctrineMappingForTheBundleEntity(): void
    {
        $configs = $this->prependWith(
            ['DoctrineBundle' => 'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle'],
            null
        );

        $this->assertCount(1, $configs);
        $mapping = $configs[0]['orm']['mappings']['CalendarBundle'];
        $this->assertSame('attribute', $mapping['type']);
        $this->assertSame('JeanSebastienChristophe\\CalendarBundle\\Entity', $mapping['prefix']);
        $this->assertFalse($mapping['is_bundle']);
        $this->assertDirectoryExists($mapping['dir']);
    }

    public function testPrependSkipsDoctrineMappingWhenAnotherEventClassIsConfigured(): void
    {
        $configs = $this->prependWith(
            ['DoctrineBundle' => 'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle'],
            CustomEvent::class
        );

        $this->assertSame([], $configs, 'An application mapping its own entity must not inherit calendar_events.');
    }

    public function testPrependSkipsDoctrineMappingWithoutDoctrineBundle(): void
    {
        $this->assertSame([], $this->prependWith([], null));
    }
}
