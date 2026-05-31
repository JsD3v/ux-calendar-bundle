<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Assets;

use PHPUnit\Framework\TestCase;

class PackageJsonTest extends TestCase
{
    public function testPackageExposesCalendarStimulusController(): void
    {
        $packageJson = json_decode(
            file_get_contents(dirname(__DIR__, 2) . '/assets/package.json'),
            true,
            512,
            \JSON_THROW_ON_ERROR
        );

        $this->assertSame('@jean-sebastien-christophe/ux-calendar-bundle', $packageJson['name']);
        $this->assertSame('dist/controller.js', $packageJson['main']);
        $this->assertSame(
            'dist/controller.js',
            $packageJson['symfony']['controllers']['calendar']['main']
        );
        $this->assertSame('calendar', $packageJson['symfony']['controllers']['calendar']['name']);
        $this->assertTrue($packageJson['symfony']['controllers']['calendar']['enabled']);
    }
}
