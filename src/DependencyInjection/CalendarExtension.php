<?php

namespace JeanSebastienChristophe\CalendarBundle\DependencyInjection;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CalendarExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__ . '/../../assets' => '@calendar-bundle',
                    ],
                ],
            ]);
        }

        $this->prependDoctrineMapping($container);
    }

    /**
     * Registers the Doctrine mapping for the bundle's own Event entity.
     *
     * Doctrine does not scan third-party bundles, so without this the shipped
     * entity is unknown to the ORM: make:migration generates nothing and the
     * first request fails with "not found in the chain configured namespaces".
     *
     * The mapping is only registered when the application actually uses the
     * bundle entity. An application mapping its own `event_class` must not
     * inherit a stray `calendar_events` table in its schema.
     */
    private function prependDoctrineMapping(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.bundles')) {
            return;
        }

        $bundles = $container->getParameter('kernel.bundles');

        if (!\is_array($bundles) || !isset($bundles['DoctrineBundle'])) {
            return;
        }

        if (!$this->usesBundleEventEntity($container)) {
            return;
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'CalendarBundle' => [
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/../Entity',
                        'prefix' => 'JeanSebastienChristophe\CalendarBundle\Entity',
                        'alias' => 'Calendar',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Resolves `calendar.event_class` from the raw configs.
     *
     * prepend() runs before load(), so the processed configuration is not
     * available yet; the last declaration wins, mirroring how the Config
     * component merges scalar nodes.
     */
    private function usesBundleEventEntity(ContainerBuilder $container): bool
    {
        foreach (array_reverse($container->getExtensionConfig('calendar')) as $config) {
            if (isset($config['event_class'])) {
                return ltrim((string) $config['event_class'], '\\') === Event::class;
            }
        }

        // No explicit configuration: the default is the bundle entity.
        return true;
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        if (!$container->hasParameter('kernel.bundles_metadata')) {
            return false;
        }

        // Check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!\is_array($bundlesMetadata) || !isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php');
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        // Vérifier les dépendances requises
        $this->checkRequiredBundles($container);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Vérifier que la classe implémente l'interface
        $this->validateEventClass($config['event_class']);

        // Stocker la configuration dans le container
        $container->setParameter('calendar.event_class', $config['event_class']);
        $container->setParameter('calendar.route_prefix', $config['route_prefix']);
        $container->setParameter('calendar.theme', $config['theme']);
        $container->setParameter('calendar.assets', $config['assets']);
        $container->setParameter('calendar.assets.include_cdn', $config['assets']['include_cdn']);
        $container->setParameter('calendar.views', $config['views']);
        $container->setParameter('calendar.features', $config['features']);

        // Charger les services
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yaml');
    }

    /**
     * Vérifie que les bundles requis sont installés
     */
    private function checkRequiredBundles(ContainerBuilder $container): void
    {
        $requiredBundles = [
            'Symfony\UX\Turbo\TurboBundle' => [
                'name' => 'Turbo Bundle',
                'package' => 'symfony/ux-turbo',
                'command' => 'composer require symfony/ux-turbo',
            ],
            'Symfony\UX\StimulusBundle\StimulusBundle' => [
                'name' => 'Stimulus Bundle',
                'package' => 'symfony/stimulus-bundle',
                'command' => 'composer require symfony/stimulus-bundle',
            ],
        ];

        $bundles = $container->getParameter('kernel.bundles');
        $missingBundles = [];

        foreach ($requiredBundles as $bundleClass => $bundleInfo) {
            if (!isset($bundles[$this->getBundleName($bundleClass)])) {
                $missingBundles[] = $bundleInfo;
            }
        }

        if (!empty($missingBundles)) {
            $this->throwMissingBundlesException($missingBundles);
        }

        // Vérifier AssetMapper
        if (!interface_exists(AssetMapperInterface::class)) {
            throw new \LogicException(
                "CalendarBundle requires Symfony AssetMapper.\n" .
                "Install it with: composer require symfony/asset-mapper\n" .
                "Then run: php bin/console importmap:install"
            );
        }
    }

    /**
     * Extrait le nom du bundle depuis son FQCN
     */
    private function getBundleName(string $bundleClass): string
    {
        $parts = explode('\\', $bundleClass);
        return end($parts);
    }

    /**
     * Lance une exception avec un message clair sur les bundles manquants
     */
    private function throwMissingBundlesException(array $missingBundles): void
    {
        $message = "CalendarBundle requires the following bundles to be installed:\n\n";

        foreach ($missingBundles as $bundle) {
            $message .= sprintf(
                "  - %s (%s)\n    Install with: %s\n\n",
                $bundle['name'],
                $bundle['package'],
                $bundle['command']
            );
        }

        $message .= "After installation, make sure to:\n";
        $message .= "  1. Clear cache: php bin/console cache:clear\n";
        $message .= "  2. Install assets: php bin/console importmap:install\n";

        throw new \LogicException($message);
    }

    /**
     * Valide que la classe d'événement implémente l'interface CalendarEventInterface
     */
    private function validateEventClass(string $eventClass): void
    {
        if (!class_exists($eventClass)) {
            throw new \InvalidArgumentException(
                sprintf('The event class "%s" does not exist.', $eventClass)
            );
        }

        if (!is_subclass_of($eventClass, CalendarEventInterface::class)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The event class "%s" must implement "%s".',
                    $eventClass,
                    CalendarEventInterface::class
                )
            );
        }

        $reflectionClass = new \ReflectionClass($eventClass);
        if (!$reflectionClass->isInstantiable()) {
            throw new \InvalidArgumentException(
                sprintf('The event class "%s" must be instantiable.', $eventClass)
            );
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            throw new \InvalidArgumentException(
                sprintf('The event class "%s" must have no required constructor arguments.', $eventClass)
            );
        }
    }
}
