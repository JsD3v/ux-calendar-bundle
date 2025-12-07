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
        private readonly string $configuredTheme = 'auto',
        private readonly ?AssetMapperInterface $assetMapper = null
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

        // Prefer AssetMapper when available, otherwise fall back to the public bundle paths
        $coreUrl = '/bundles/calendar/styles/calendar-core.css';
        if ($this->assetMapper !== null) {
            $coreAsset = $this->assetMapper->getAsset('styles/calendar-core.css');
            $coreUrl = $coreAsset ? $coreAsset->publicPath : $coreUrl;
        }
        $coreCSS = '<link rel="stylesheet" href="' . $coreUrl . '">';

        $themeFile = match($theme) {
            'bootstrap' => 'styles/themes/bootstrap.css',
            'tailwind' => 'styles/themes/tailwind.css',
            default => 'styles/themes/default.css',
        };

        $themeUrl = '/bundles/calendar/' . $themeFile;
        if ($this->assetMapper !== null) {
            $themeAsset = $this->assetMapper->getAsset($themeFile);
            $themeUrl = $themeAsset ? $themeAsset->publicPath : $themeUrl;
        }
        $themeCSS = '<link rel="stylesheet" href="' . $themeUrl . '">';

        return $coreCSS . "\n" . $themeCSS;
    }
}
