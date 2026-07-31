<?php

declare(strict_types=1);

namespace QUITests\Projects\Site;

use PHPUnit\Framework\TestCase;
use QUI\Interfaces\Projects\Site as SiteInterface;
use QUI\Projects\Project;
use QUI\Projects\Site\Canonical;
use ReflectionMethod;

class CanonicalVhostRouteTest extends TestCase
{
    public function testPathLanguageIsNotDuplicatedInCanonicalUrl(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHost')->willReturn('https://www.example.eu');
        $Project->method('getVHostPath')->willReturn('es');
        $Project->method('getVHostBaseUrl')->willReturn(
            'https://www.example.eu/' .
            (trim(URL_DIR, '/') === '' ? '' : trim(URL_DIR, '/') . '/') .
            'es/'
        );

        $Site = $this->createMock(SiteInterface::class);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getCanonical')->willReturn(URL_DIR . 'es/example');
        $Site->method('getAttribute')->willReturn(false);

        $Canonical = new Canonical($Site);
        $buildCanonicalUrl = new ReflectionMethod($Canonical, 'buildCanonicalUrl');

        self::assertSame(
            'https://www.example.eu/' . trim(URL_DIR, '/') .
            (trim(URL_DIR, '/') === '' ? '' : '/') .
            'es/example',
            $buildCanonicalUrl->invoke($Canonical)
        );
    }

    public function testRelativeCanonicalGetsPathLanguagePrefix(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHost')->willReturn('https://www.example.eu');
        $Project->method('getVHostPath')->willReturn('es');
        $Project->method('getVHostBaseUrl')->willReturn(
            'https://www.example.eu/' .
            (trim(URL_DIR, '/') === '' ? '' : trim(URL_DIR, '/') . '/') .
            'es/'
        );

        $Site = $this->createMock(SiteInterface::class);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getCanonical')->willReturn('custom');
        $Site->method('getAttribute')->willReturn(false);

        $Canonical = new Canonical($Site);
        $buildCanonicalUrl = new ReflectionMethod($Canonical, 'buildCanonicalUrl');

        self::assertSame(
            'https://www.example.eu/' . trim(URL_DIR . 'es/custom', '/'),
            $buildCanonicalUrl->invoke($Canonical)
        );
    }

    public function testPathLanguageRootCanonicalEndsWithSlash(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHost')->willReturn('https://www.example.eu');
        $Project->method('getVHostPath')->willReturn('es');
        $Project->method('getVHostBaseUrl')->willReturn(
            'https://www.example.eu/' .
            (trim(URL_DIR, '/') === '' ? '' : trim(URL_DIR, '/') . '/') .
            'es/'
        );

        $Site = $this->createMock(SiteInterface::class);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getCanonical')->willReturn(URL_DIR . 'es/');
        $Site->method('getAttribute')->willReturn(false);

        $Canonical = new Canonical($Site);
        $buildCanonicalUrl = new ReflectionMethod($Canonical, 'buildCanonicalUrl');
        $installationPath = trim(URL_DIR, '/');
        $expectedPath = $installationPath === '' ? 'es/' : $installationPath . '/es/';

        self::assertSame(
            'https://www.example.eu/' . $expectedPath,
            $buildCanonicalUrl->invoke($Canonical)
        );
    }
}
