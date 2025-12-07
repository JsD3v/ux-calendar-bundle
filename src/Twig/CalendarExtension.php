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

        // Use AssetMapper logical paths
        $coreUrl = '/bundles/calendar/styles/calendar-core.css';
        if ($this->assetMapper !== null) {
            $coreAsset = $this->assetMapper->getAsset('@calendar-bundle/styles/calendar-core.css');
            $coreUrl = $coreAsset ? $coreAsset->publicPath : $coreUrl;
        }
        $coreCSS = '<link rel="stylesheet" href="' . $coreUrl . '">';

        $themeFile = match($theme) {
            'bootstrap' => '@calendar-bundle/styles/themes/bootstrap.css',
            'tailwind' => '@calendar-bundle/styles/themes/tailwind.css',
            default => '@calendar-bundle/styles/themes/default.css',
        };

        $themeUrl = str_replace('@calendar-bundle/', '/bundles/calendar/', $themeFile);
        if ($this->assetMapper !== null) {
            $themeAsset = $this->assetMapper->getAsset($themeFile);
            $themeUrl = $themeAsset ? $themeAsset->publicPath : $themeUrl;
        }
        $themeCSS = '<link rel="stylesheet" href="' . $themeUrl . '">';

        return $coreCSS . "\n" . $themeCSS;
    }
}
