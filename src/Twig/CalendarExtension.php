<?php

namespace JeanSebastienChristophe\CalendarBundle\Twig;

use JeanSebastienChristophe\CalendarBundle\Service\ThemeDetector;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CalendarExtension extends AbstractExtension
{
    public function __construct(
        private readonly ThemeDetector $themeDetector,
        private readonly string $configuredTheme = 'auto'
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('calendar_theme_css', [$this, 'getThemeCss'], [
                'is_safe' => ['html']
            ]),
            new TwigFunction('calendar_theme', [$this, 'getTheme']),
        ];
    }

    public function getTheme(): string
    {
        if ($this->configuredTheme === 'auto') {
            return $this->themeDetector->detectTheme();
        }

        return $this->configuredTheme;
    }

    public function getThemeCss(): string
    {
        $theme = $this->getTheme();

        $coreCSS = '<link rel="stylesheet" href="/bundles/calendar/styles/calendar-core.css">';

        $themeCSS = match($theme) {
            'bootstrap' => '<link rel="stylesheet" href="/bundles/calendar/styles/themes/bootstrap.css">',
            'tailwind' => '<link rel="stylesheet" href="/bundles/calendar/styles/themes/tailwind.css">',
            default => '<link rel="stylesheet" href="/bundles/calendar/styles/themes/default.css">',
        };

        return $coreCSS . "\n" . $themeCSS;
    }
}
