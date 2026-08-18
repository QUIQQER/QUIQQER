<?php

declare(strict_types=1);

namespace QUITests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Rewrite;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

class RewriteCanonicalPathTest extends TestCase
{
    /**
     * @param array<string, string> $query
     */
    #[DataProvider('caseRedirectProvider')]
    public function testCaseOnlyPathDifferenceReturnsCanonicalRedirectUrl(
        string $requestPath,
        string $canonicalUrl,
        string $method,
        array $query,
        ?string $expected
    ): void {
        $getRedirectUrl = new ReflectionMethod(
            Rewrite::class,
            'getCanonicalCaseRedirectUrl'
        );
        $Request = Request::create($requestPath, $method, $query);

        self::assertSame(
            $expected,
            $getRedirectUrl->invoke(
                null,
                $requestPath,
                $canonicalUrl,
                $Request
            )
        );
    }

    /**
     * @return iterable<string, array{string, string, string, array<string, string>, string|null}>
     */
    public static function caseRedirectProvider(): iterable
    {
        yield 'root language' => [
            '/blog',
            '/Blog',
            Request::METHOD_GET,
            [],
            '/Blog'
        ];

        yield 'path language' => [
            '/de/blog',
            '/de/Blog',
            Request::METHOD_GET,
            [],
            '/de/Blog'
        ];

        yield 'url encoded Unicode path' => [
            '/de/blog/krümelmonster-tag',
            '/de/Blog/Kr%C3%BCmelmonster-Tag',
            Request::METHOD_GET,
            [],
            '/de/Blog/Kr%C3%BCmelmonster-Tag'
        ];

        yield 'public query parameters' => [
            '/de/blog',
            '/de/Blog',
            Request::METHOD_GET,
            [
                '_url' => 'de/blog',
                'currency' => 'EUR'
            ],
            '/de/Blog?currency=EUR'
        ];

        yield 'head request' => [
            '/blog',
            '/Blog',
            Request::METHOD_HEAD,
            [],
            '/Blog'
        ];

        yield 'exact path' => [
            '/Blog',
            '/Blog',
            Request::METHOD_GET,
            [],
            null
        ];

        yield 'different path' => [
            '/news',
            '/Blog',
            Request::METHOD_GET,
            [],
            null
        ];

        yield 'post request' => [
            '/blog',
            '/Blog',
            Request::METHOD_POST,
            [],
            null
        ];
    }
}
