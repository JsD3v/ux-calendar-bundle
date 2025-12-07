<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Twig;

use JeanSebastienChristophe\CalendarBundle\Service\ThemeDetector;
use JeanSebastienChristophe\CalendarBundle\Twig\CalendarExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class CalendarExtensionTest extends TestCase
{
    private ThemeDetector $themeDetector;

    protected function setUp(): void
    {
        $this->themeDetector = $this->createMock(ThemeDetector::class);
    }

    public function testGetFunctions(): void
    {
        $extension = new CalendarExtension($this->themeDetector);
        $functions = $extension->getFunctions();

        $this->assertIsArray($functions);
        $this->assertCount(2, $functions);

        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertInstanceOf(TwigFunction::class, $functions[1]);

        $functionNames = array_map(fn($f) => $f->getName(), $functions);
        $this->assertContains('calendar_theme_css', $functionNames);
        $this->assertContains('calendar_theme', $functionNames);
    }

    public function testGetThemeCssFunctionIsSafe(): void
    {
        $extension = new CalendarExtension($this->themeDetector);
        $functions = $extension->getFunctions();

        $themeCssFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'calendar_theme_css') {
                $themeCssFunction = $function;
                break;
            }
        }

        $this->assertNotNull($themeCssFunction);

        $reflection = new \ReflectionClass($themeCssFunction);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $options = $property->getValue($themeCssFunction);

        $this->assertArrayHasKey('is_safe', $options);
        $this->assertEquals(['html'], $options['is_safe']);
    }

    public function testGetThemeWithAutoConfigurationUsesDetector(): void
    {
        $this->themeDetector->method('detectTheme')
            ->willReturn('bootstrap');

        $extension = new CalendarExtension($this->themeDetector, 'auto');
        $theme = $extension->getTheme();

        $this->assertEquals('bootstrap', $theme);
    }

    public function testGetThemeWithExplicitBootstrapConfiguration(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'bootstrap');
        $theme = $extension->getTheme();

        $this->assertEquals('bootstrap', $theme);
    }

    public function testGetThemeWithExplicitTailwindConfiguration(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'tailwind');
        $theme = $extension->getTheme();

        $this->assertEquals('tailwind', $theme);
    }

    public function testGetThemeWithExplicitDefaultConfiguration(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'default');
        $theme = $extension->getTheme();

        $this->assertEquals('default', $theme);
    }

    public function testGetThemeAutoDoesNotCallDetectorWhenExplicitlySet(): void
    {
        $this->themeDetector->expects($this->never())
            ->method('detectTheme');

        $extension = new CalendarExtension($this->themeDetector, 'bootstrap');
        $extension->getTheme();
    }

    public function testGetThemeCssWithBootstrapTheme(): void
    {
        $this->themeDetector->method('detectTheme')
            ->willReturn('bootstrap');

        $extension = new CalendarExtension($this->themeDetector, 'auto');
        $css = $extension->getThemeCss();

        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/calendar-core.css">', $css);
        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/themes/bootstrap.css">', $css);
        $this->assertStringNotContainsString('tailwind.css', $css);
        $this->assertStringNotContainsString('themes/default.css', $css);
    }

    public function testGetThemeCssWithTailwindTheme(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'tailwind');
        $css = $extension->getThemeCss();

        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/calendar-core.css">', $css);
        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/themes/tailwind.css">', $css);
        $this->assertStringNotContainsString('bootstrap.css', $css);
        $this->assertStringNotContainsString('themes/default.css', $css);
    }

    public function testGetThemeCssWithDefaultTheme(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'default');
        $css = $extension->getThemeCss();

        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/calendar-core.css">', $css);
        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/themes/default.css">', $css);
        $this->assertStringNotContainsString('bootstrap.css', $css);
        $this->assertStringNotContainsString('tailwind.css', $css);
    }

    public function testGetThemeCssIncludesCoreStylesForAllThemes(): void
    {
        $themes = ['bootstrap', 'tailwind', 'default'];

        foreach ($themes as $theme) {
            $extension = new CalendarExtension($this->themeDetector, $theme);
            $css = $extension->getThemeCss();

            $this->assertStringContainsString(
                '<link rel="stylesheet" href="/bundles/calendar/styles/calendar-core.css">',
                $css,
                "Core CSS should be included for theme: {$theme}"
            );
        }
    }

    public function testGetThemeCssContainsNewlineBetweenLinks(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'bootstrap');
        $css = $extension->getThemeCss();

        $this->assertStringContainsString("\n", $css);

        $lines = explode("\n", $css);
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('calendar-core.css', $lines[0]);
        $this->assertStringContainsString('bootstrap.css', $lines[1]);
    }

    public function testGetThemeCssWithCustomThemeFallsBackToDefault(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'nonexistent-theme');
        $css = $extension->getThemeCss();

        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/calendar-core.css">', $css);
        $this->assertStringContainsString('<link rel="stylesheet" href="/bundles/calendar/styles/themes/default.css">', $css);
    }

    public function testGetThemeFunctionCallable(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'bootstrap');
        $functions = $extension->getFunctions();

        $themeFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'calendar_theme') {
                $themeFunction = $function;
                break;
            }
        }

        $this->assertNotNull($themeFunction);

        $callable = $themeFunction->getCallable();
        $this->assertIsCallable($callable);
        $this->assertEquals('bootstrap', call_user_func($callable));
    }

    public function testGetThemeCssFunctionCallable(): void
    {
        $extension = new CalendarExtension($this->themeDetector, 'bootstrap');
        $functions = $extension->getFunctions();

        $themeCssFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'calendar_theme_css') {
                $themeCssFunction = $function;
                break;
            }
        }

        $this->assertNotNull($themeCssFunction);

        $callable = $themeCssFunction->getCallable();
        $this->assertIsCallable($callable);

        $result = call_user_func($callable);
        $this->assertIsString($result);
        $this->assertStringContainsString('calendar-core.css', $result);
    }

    public function testExtensionDefaultsToAutoTheme(): void
    {
        $this->themeDetector->method('detectTheme')
            ->willReturn('tailwind');

        // When no second parameter is provided, it should default to 'auto'
        $extension = new CalendarExtension($this->themeDetector);
        $theme = $extension->getTheme();

        $this->assertEquals('tailwind', $theme);
    }
}
