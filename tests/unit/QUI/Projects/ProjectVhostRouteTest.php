<?php

declare(strict_types=1);

namespace QUITests\Projects;

use PHPUnit\Framework\TestCase;
use QUI\Projects\Project;
use ReflectionClass;
use ReflectionProperty;

class ProjectVhostRouteTest extends TestCase
{
    public function testPathLanguageBaseUrlContainsInstallationAndLanguagePath(): void
    {
        $Project = (new ReflectionClass(Project::class))->newInstanceWithoutConstructor();

        (new ReflectionProperty(Project::class, 'name'))->setValue($Project, 'example');
        (new ReflectionProperty(Project::class, 'lang'))->setValue($Project, 'es');
        (new ReflectionProperty(Project::class, 'vhostRoute'))->setValue($Project, [
            'host' => 'www.example.eu',
            'httpshost' => 'secure.example.eu',
            'path' => 'es',
            'project' => 'example',
            'lang' => 'es'
        ]);

        self::assertSame('www.example.eu', $Project->getHost());
        self::assertSame('es', $Project->getVHostPath());
        self::assertSame('https://secure.example.eu', $Project->getVHost(true, true));
        $installationPath = trim(URL_DIR, '/');
        $expectedPath = $installationPath === '' ? 'es/' : $installationPath . '/es/';

        self::assertSame(
            'https://secure.example.eu/' . $expectedPath,
            $Project->getVHostBaseUrl()
        );
    }

    public function testRootLanguageBaseUrlHasNoLanguagePath(): void
    {
        $Project = (new ReflectionClass(Project::class))->newInstanceWithoutConstructor();

        (new ReflectionProperty(Project::class, 'name'))->setValue($Project, 'example');
        (new ReflectionProperty(Project::class, 'lang'))->setValue($Project, 'en');
        (new ReflectionProperty(Project::class, 'vhostRoute'))->setValue($Project, [
            'host' => 'www.example.eu',
            'httpshost' => '',
            'path' => '',
            'project' => 'example',
            'lang' => 'en'
        ]);

        self::assertSame('', $Project->getVHostPath());
        $installationPath = trim(URL_DIR, '/');
        $expectedPath = $installationPath === '' ? '' : $installationPath . '/';

        self::assertSame(
            'https://www.example.eu/' . $expectedPath,
            $Project->getVHostBaseUrl()
        );
    }
}
