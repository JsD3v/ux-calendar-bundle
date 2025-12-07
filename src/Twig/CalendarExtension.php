<?php

namespace JeanSebastienChristophe\CalendarBundle\Twig;

use JeanSebastienChristophe\CalendarBundle\Service\ThemeDetector;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CalendarExtension extends AbstractExtension
{
    public function __construct(
        private readonly ThemeDetector $themeDetector,
        private readonly AssetMapperInterface $assetMapper,
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

        // Get the asset paths from AssetMapper
        $coreAsset = $this->assetMapper->getAsset('styles/calendar-core.css');
        $coreUrl = $coreAsset ? $coreAsset->publicPath : '/bundles/calendar/styles/calendar-core.css';
        $coreCSS = '<link rel="stylesheet" href="' . $coreUrl . '">';

        $themeFile = match($theme) {
            'bootstrap' => 'styles/themes/bootstrap.css',
            'tailwind' => 'styles/themes/tailwind.css',
            default => 'styles/themes/default.css',
        };

        $themeAsset = $this->assetMapper->getAsset($themeFile);
        $themeUrl = $themeAsset ? $themeAsset->publicPath : '/bundles/calendar/' . $themeFile;
        $themeCSS = '<link rel="stylesheet" href="' . $themeUrl . '">';

        return $coreCSS . "\n" . $themeCSS;
    }
}
