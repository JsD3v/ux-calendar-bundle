<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests;

use JeanSebastienChristophe\CalendarBundle\CalendarBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CalendarBundleTest extends TestCase
{
    private CalendarBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new CalendarBundle();
    }

    public function testBundleExtendsSymfonyBundle(): void
    {
        $this->assertInstanceOf(Bundle::class, $this->bundle);
    }

    public function testGetPathReturnsCorrectDirectory(): void
    {
        $path = $this->bundle->getPath();

        // The path should point to the directory containing the bundle
        // It should end with 'calendar-bundle' (the project root)
        $this->assertIsString($path);
        $this->assertDirectoryExists($path);

        // Verify it's the parent directory of src/
        $expectedPath = dirname(__DIR__);
        $this->assertEquals($expectedPath, $path);
    }

    public function testGetPathIsAbsolute(): void
    {
        $path = $this->bundle->getPath();

        // Check if path is absolute (starts with / on Unix or drive letter on Windows)
        $this->assertTrue(
            $path[0] === '/' || (strlen($path) > 1 && $path[1] === ':'),
            'Path should be absolute'
        );
    }

    public function testGetPathContainsSrcDirectory(): void
    {
        $path = $this->bundle->getPath();
        $srcPath = $path . '/src';

        $this->assertDirectoryExists($srcPath);
    }

    public function testGetPathContainsCalendarBundleFile(): void
    {
        $path = $this->bundle->getPath();
        $bundleFile = $path . '/src/CalendarBundle.php';

        $this->assertFileExists($bundleFile);
    }

    public function testGetPathConsistentAcrossMultipleCalls(): void
    {
        $path1 = $this->bundle->getPath();
        $path2 = $this->bundle->getPath();

        $this->assertEquals($path1, $path2);
    }

    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new CalendarBundle();

        $this->assertInstanceOf(CalendarBundle::class, $bundle);
    }

    public function testGetNameReturnsCorrectBundleName(): void
    {
        $name = $this->bundle->getName();

        $this->assertEquals('CalendarBundle', $name);
    }

    public function testGetNamespaceReturnsCorrectNamespace(): void
    {
        $namespace = $this->bundle->getNamespace();

        $this->assertEquals('JeanSebastienChristophe\\CalendarBundle', $namespace);
    }

    public function testGetContainerExtensionReturnsExtension(): void
    {
        $extension = $this->bundle->getContainerExtension();

        $this->assertNotNull($extension);
        $this->assertInstanceOf(
            'JeanSebastienChristophe\\CalendarBundle\\DependencyInjection\\CalendarExtension',
            $extension
        );
    }
}
