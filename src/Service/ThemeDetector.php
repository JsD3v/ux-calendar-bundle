<?php

namespace JeanSebastienChristophe\CalendarBundle\Service;

use Symfony\Component\AssetMapper\AssetMapperInterface;

class ThemeDetector
{
    public function __construct(
        private readonly ?AssetMapperInterface $assetMapper = null,
        private readonly ?string $projectDir = null
    ) {
    }

    /**
     * Détecte automatiquement le framework CSS utilisé
     */
    public function detectTheme(): string
    {
        // Méthode 1 : Via AssetMapper (recommandé)
        if ($this->assetMapper !== null) {
            return $this->detectViaAssetMapper();
        }

        // Méthode 2 : Via importmap.php
        if ($this->projectDir !== null) {
            return $this->detectViaImportMap();
        }

        return 'default';
    }

    private function detectViaAssetMapper(): string
    {
        try {
            $assets = $this->assetMapper->allAssets();

            foreach ($assets as $asset) {
                $path = strtolower($asset->logicalPath);

                // Détecter Bootstrap
                if (str_contains($path, 'bootstrap')) {
                    return 'bootstrap';
                }

                // Détecter Tailwind
                if (str_contains($path, 'tailwind')) {
                    return 'tailwind';
                }
            }
        } catch (\Exception $e) {
            // Si AssetMapper non disponible, fallback
        }

        return 'default';
    }

    private function detectViaImportMap(): string
    {
        $importMapPath = $this->projectDir . '/importmap.php';

        if (!file_exists($importMapPath)) {
            return 'default';
        }

        try {
            $importMap = require $importMapPath;
            $imports = $importMap['imports'] ?? [];

            // Chercher Bootstrap
            foreach ($imports as $key => $value) {
                $keyLower = strtolower($key);
                $valueLower = is_string($value) ? strtolower($value) : '';

                if (str_contains($keyLower, 'bootstrap') ||
                    str_contains($valueLower, 'bootstrap')) {
                    return 'bootstrap';
                }

                if (str_contains($keyLower, 'tailwind') ||
                    str_contains($valueLower, 'tailwind')) {
                    return 'tailwind';
                }
            }
        } catch (\Exception $e) {
            // Si erreur de lecture, fallback
        }

        return 'default';
    }
}
