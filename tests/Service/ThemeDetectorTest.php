<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Service;

use JeanSebastienChristophe\CalendarBundle\Service\ThemeDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;

class ThemeDetectorTest extends TestCase
{
    public function testDetectThemeReturnsDefaultWhenNoAssetMapperOrProjectDir(): void
    {
        $detector = new ThemeDetector();
        $theme = $detector->detectTheme();

        $this->assertEquals('default', $theme);
    }

    public function testDetectThemeViaAssetMapperBootstrap(): void
    {
        // Create a stub implementation since MappedAsset is final
        $bootstrapAsset = new class {
            public string $logicalPath = 'vendor/bootstrap/dist/css/bootstrap.min.css';

            public function __get(string $name)
            {
                return $this->$name ?? null;
            }
        };

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->method('allAssets')
            ->willReturn([$bootstrapAsset]);

        $detector = new ThemeDetector($assetMapper);
        $theme = $detector->detectTheme();

        $this->assertEquals('bootstrap', $theme);
    }

    public function testDetectThemeViaAssetMapperTailwind(): void
    {
        // Create a stub implementation since MappedAsset is final
        $tailwindAsset = new class {
            public string $logicalPath = 'styles/tailwind.css';

            public function __get(string $name)
            {
                return $this->$name ?? null;
            }
        };

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->method('allAssets')
            ->willReturn([$tailwindAsset]);

        $detector = new ThemeDetector($assetMapper);
        $theme = $detector->detectTheme();

        $this->assertEquals('tailwind', $theme);
    }

    public function testDetectThemeViaAssetMapperDefaultWhenNoFrameworkFound(): void
    {
        // Create a stub implementation since MappedAsset is final
        $customAsset = new class {
            public string $logicalPath = 'styles/custom.css';

            public function __get(string $name)
            {
                return $this->$name ?? null;
            }
        };

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->method('allAssets')
            ->willReturn([$customAsset]);

        $detector = new ThemeDetector($assetMapper);
        $theme = $detector->detectTheme();

        $this->assertEquals('default', $theme);
    }

    public function testDetectThemeViaAssetMapperCaseInsensitive(): void
    {
        // Create a stub implementation since MappedAsset is final
        $bootstrapAsset = new class {
            public string $logicalPath = 'vendor/BOOTSTRAP/dist/css/bootstrap.min.css';

            public function __get(string $name)
            {
                return $this->$name ?? null;
            }
        };

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->method('allAssets')
            ->willReturn([$bootstrapAsset]);

        $detector = new ThemeDetector($assetMapper);
        $theme = $detector->detectTheme();

        $this->assertEquals('bootstrap', $theme);
    }

    public function testDetectThemeViaAssetMapperHandlesException(): void
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->method('allAssets')
            ->willThrowException(new \Exception('AssetMapper error'));

        $detector = new ThemeDetector($assetMapper);
        $theme = $detector->detectTheme();

        $this->assertEquals('default', $theme);
    }

    public function testDetectThemeViaImportMapBootstrap(): void
    {
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-' . uniqid();
        mkdir($tempDir);

        $importMapContent = <<<'PHP'
<?php
return [
    'imports' => [
        'bootstrap' => [
            'path' => 'vendor/bootstrap/dist/js/bootstrap.min.js',
        ],
    ],
];
PHP;

        file_put_contents($tempDir . '/importmap.php', $importMapContent);

        $detector = new ThemeDetector(null, $tempDir);
        $theme = $detector->detectTheme();

        // Clean up
        unlink($tempDir . '/importmap.php');
        rmdir($tempDir);

        $this->assertEquals('bootstrap', $theme);
    }

    public function testDetectThemeViaImportMapTailwind(): void
    {
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-' . uniqid();
        mkdir($tempDir);

        $importMapContent = <<<'PHP'
<?php
return [
    'imports' => [
        'app' => [
            'path' => 'app.js',
        ],
        'tailwindcss' => [
            'path' => 'vendor/tailwindcss/tailwind.min.js',
        ],
    ],
];
PHP;

        file_put_contents($tempDir . '/importmap.php', $importMapContent);

        $detector = new ThemeDetector(null, $tempDir);
        $theme = $detector->detectTheme();

        // Clean up
        unlink($tempDir . '/importmap.php');
        rmdir($tempDir);

        $this->assertEquals('tailwind', $theme);
    }

    public function testDetectThemeViaImportMapDefaultWhenNoFrameworkFound(): void
    {
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-' . uniqid();
        mkdir($tempDir);

        $importMapContent = <<<'PHP'
<?php
return [
    'imports' => [
        'app' => [
            'path' => 'app.js',
        ],
    ],
];
PHP;

        file_put_contents($tempDir . '/importmap.php', $importMapContent);

        $detector = new ThemeDetector(null, $tempDir);
        $theme = $detector->detectTheme();

        // Clean up
        unlink($tempDir . '/importmap.php');
        rmdir($tempDir);

        $this->assertEquals('default', $theme);
    }

    public function testDetectThemeViaImportMapWhenFileNotExists(): void
    {
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-nonexistent-' . uniqid();

        $detector = new ThemeDetector(null, $tempDir);
        $theme = $detector->detectTheme();

        $this->assertEquals('default', $theme);
    }

    public function testDetectThemeViaImportMapHandlesEmptyFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-' . uniqid();
        mkdir($tempDir);

        // Create empty PHP file (valid but returns nothing)
        file_put_contents($tempDir . '/importmap.php', '<?php');

        $detector = new ThemeDetector(null, $tempDir);
        $theme = $detector->detectTheme();

        // Clean up
        unlink($tempDir . '/importmap.php');
        rmdir($tempDir);

        $this->assertEquals('default', $theme);
    }

    public function testDetectThemeViaImportMapHandlesMissingImportsKey(): void
    {
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-' . uniqid();
        mkdir($tempDir);

        $importMapContent = <<<'PHP'
<?php
return [
    'other_key' => [],
];
PHP;

        file_put_contents($tempDir . '/importmap.php', $importMapContent);

        $detector = new ThemeDetector(null, $tempDir);
        $theme = $detector->detectTheme();

        // Clean up
        unlink($tempDir . '/importmap.php');
        rmdir($tempDir);

        $this->assertEquals('default', $theme);
    }

    public function testDetectThemeViaImportMapChecksValueString(): void
    {
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-' . uniqid();
        mkdir($tempDir);

        $importMapContent = <<<'PHP'
<?php
return [
    'imports' => [
        'my-bootstrap' => 'path/to/bootstrap/file.js',
    ],
];
PHP;

        file_put_contents($tempDir . '/importmap.php', $importMapContent);

        $detector = new ThemeDetector(null, $tempDir);
        $theme = $detector->detectTheme();

        // Clean up
        unlink($tempDir . '/importmap.php');
        rmdir($tempDir);

        $this->assertEquals('bootstrap', $theme);
    }

    public function testDetectThemePrefersAssetMapperOverImportMap(): void
    {
        // Create importmap with tailwind
        $tempDir = sys_get_temp_dir() . '/calendar-bundle-test-' . uniqid();
        mkdir($tempDir);

        $importMapContent = <<<'PHP'
<?php
return [
    'imports' => [
        'tailwindcss' => 'path/to/tailwind.js',
    ],
];
PHP;

        file_put_contents($tempDir . '/importmap.php', $importMapContent);

        // Create AssetMapper with bootstrap - use stub since MappedAsset is final
        $bootstrapAsset = new class {
            public string $logicalPath = 'vendor/bootstrap/dist/css/bootstrap.min.css';

            public function __get(string $name)
            {
                return $this->$name ?? null;
            }
        };

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->method('allAssets')
            ->willReturn([$bootstrapAsset]);

        // AssetMapper should take precedence
        $detector = new ThemeDetector($assetMapper, $tempDir);
        $theme = $detector->detectTheme();

        // Clean up
        unlink($tempDir . '/importmap.php');
        rmdir($tempDir);

        $this->assertEquals('bootstrap', $theme);
    }
}
