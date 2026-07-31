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

    private function getExpectedPath(string $path): string
    {
        $installationPath = trim(URL_DIR, '/');

        if ($installationPath === '') {
            return $path;
        }

        return $installationPath . '/' . $path;
    }
}
