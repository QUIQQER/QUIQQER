<?php

declare(strict_types=1);

namespace QUITests;

use PHPUnit\Framework\TestCase;
use QUI\Rewrite;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

class RewriteVhostRouteTest extends TestCase
{
    public function testPathLanguageRouteContainsLanguagePrefix(): void
    {
        $buildRoutePath = new ReflectionMethod(
            Rewrite::class,
            'buildProjectLanguageRoutePath'
        );

        self::assertSame(
            '/' . $this->getExpectedPath('es/example'),
            $buildRoutePath->invoke(null, 'es', 'example')
        );
    }

    public function testPathLanguageRootEndsWithSlash(): void
    {
        $buildRoutePath = new ReflectionMethod(
            Rewrite::class,
            'buildProjectLanguageRoutePath'
        );

        self::assertSame(
            '/' . $this->getExpectedPath('es/'),
            $buildRoutePath->invoke(null, 'es', '')
        );
    }

    public function testRootLanguageRouteRemovesLanguagePrefix(): void
    {
        $buildRoutePath = new ReflectionMethod(
            Rewrite::class,
            'buildProjectLanguageRoutePath'
        );

        self::assertSame(
            '/' . $this->getExpectedPath('example'),
            $buildRoutePath->invoke(null, '', 'example')
        );
    }

    public function testInternalRewriteParameterIsNotAppendedToRedirect(): void
    {
        $appendPublicQueryString = new ReflectionMethod(
            Rewrite::class,
            'appendPublicQueryString'
        );
        $Request = Request::create('/es/?_url=es&currency=EUR');

        self::assertSame(
            'https://www.example.eu/es/?currency=EUR',
            $appendPublicQueryString->invoke(
                null,
                'https://www.example.eu/es/',
                $Request
            )
        );
    }

    public function testCurrentProjectLanguageRouteUrlDecodesUnicodePath(): void
    {
        $buildCurrentRouteUrl = new ReflectionMethod(
            Rewrite::class,
            'buildCurrentProjectLanguageRouteUrl'
        );
        $Request = Request::create(
            'https://nerdspot.events/de/Blog/Kr%C3%BCmelmonster-Tag'
            . '?_url=de%2FBlog%2FKr%C3%BCmelmonster-Tag&currency=EUR'
        );

        self::assertSame(
            'https://nerdspot.events/de/Blog/Krümelmonster-Tag?currency=EUR',
            $buildCurrentRouteUrl->invoke(null, $Request)
        );
    }

    private function getExpectedPath(string $path): string
    {
        $installationPath = trim(URL_DIR, '/');

        if ($installationPath === '') {
            return $path;
        }

        return $installationPath . '/' . $path;
    }
}
