<?php

declare(strict_types=1);

namespace QUITests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Rewrite;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

class RewriteCanonicalHostTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $vhosts
     */
    #[DataProvider('canonicalRedirectProvider')]
    public function testCanonicalRedirectUsesConfiguredHosts(
        string $requestUrl,
        array $vhosts,
        bool $forceHttps,
        string $wwwRedirect,
        string $globalHost,
        string $globalHttpsHost,
        ?string $expected
    ): void {
        $getRedirectUrl = new ReflectionMethod(
            Rewrite::class,
            'getCanonicalHostRedirectUrl'
        );

        self::assertSame(
            $expected,
            $getRedirectUrl->invoke(
                null,
                Request::create($requestUrl),
                $vhosts,
                $forceHttps,
                $wwwRedirect,
                $globalHost,
                $globalHttpsHost
            )
        );
    }

    public function testCanonicalRedirectHonorsTrustedProxyScheme(): void
    {
        $trustedProxies = Request::getTrustedProxies();
        $trustedHeaderSet = Request::getTrustedHeaderSet();
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PROTO);

        try {
            $Request = Request::create(
                'http://example.com/path',
                server: [
                    'REMOTE_ADDR' => '127.0.0.1',
                    'HTTP_X_FORWARDED_PROTO' => 'https'
                ]
            );
            $getRedirectUrl = new ReflectionMethod(
                Rewrite::class,
                'getCanonicalHostRedirectUrl'
            );

            self::assertNull(
                $getRedirectUrl->invoke(
                    null,
                    $Request,
                    [
                        'example.com' => [
                            'project' => 'example'
                        ]
                    ],
                    true,
                    '',
                    '',
                    ''
                )
            );
        } finally {
            Request::setTrustedProxies($trustedProxies, $trustedHeaderSet);
        }
    }

    /**
     * @return iterable<string, array{
     *     string,
     *     array<array-key, mixed>,
     *     bool,
     *     string,
     *     string,
     *     string,
     *     string|null
     * }>
     */
    public static function canonicalRedirectProvider(): iterable
    {
        $vhosts = [
            'www.example.com' => [
                'project' => 'example',
                'httpshost' => 'secure.example.com'
            ]
        ];

        yield 'unknown host uses canonical vhost for HTTPS redirect' => [
            'http://attacker.example/path?query=value',
            $vhosts,
            true,
            '',
            'http://global.example.com',
            'https://secure-global.example.com',
            'https://secure.example.com/path?query=value'
        ];

        yield 'unknown host and port do not influence redirect authority' => [
            'http://attacker.example:8080/path',
            [
                'example.com' => [
                    'project' => 'example'
                ]
            ],
            true,
            '',
            '',
            '',
            'https://example.com/path'
        ];

        yield 'known host redirects to configured HTTPS host' => [
            'http://www.example.com/path',
            $vhosts,
            true,
            '',
            '',
            '',
            'https://secure.example.com/path'
        ];

        yield 'configured HTTPS host is recognized' => [
            'https://secure.example.com/path',
            $vhosts,
            true,
            '',
            '',
            '',
            null
        ];

        yield 'configured HTTPS host remains canonical with www setting' => [
            'https://secure.example.com/path',
            $vhosts,
            false,
            'www',
            '',
            '',
            null
        ];

        yield 'known host preserves port for www redirect' => [
            'http://example.com:8080/path',
            [
                'example.com' => [
                    'project' => 'example'
                ]
            ],
            false,
            'www',
            '',
            '',
            'http://www.example.com:8080/path'
        ];

        yield 'known host removes www prefix' => [
            'http://www.example.com/path',
            [
                'www.example.com' => [
                    'project' => 'example'
                ]
            ],
            false,
            'nonwww',
            '',
            '',
            'http://example.com/path'
        ];

        yield 'wildcard host preserves matched request host' => [
            'http://shop.example.com/path',
            [
                '*.example.com' => [
                    'project' => 'example'
                ]
            ],
            true,
            '',
            '',
            '',
            'https://shop.example.com/path'
        ];

        yield 'unknown host uses global fallback without vhosts' => [
            'http://attacker.example/path',
            [],
            true,
            '',
            'http://global.example.com/cms/',
            'https://secure-global.example.com/cms/',
            'https://secure-global.example.com/path'
        ];

        yield 'unknown host uses www setting only on canonical host' => [
            'http://attacker.example/path',
            [
                'example.com' => [
                    'project' => 'example'
                ]
            ],
            false,
            'www',
            '',
            '',
            'http://www.example.com/path'
        ];

        yield 'inactive canonical redirect leaves unknown host untouched' => [
            'http://attacker.example/path',
            $vhosts,
            false,
            '',
            '',
            '',
            null
        ];
    }
}
