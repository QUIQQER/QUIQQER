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
        $base = ['project' => 'example'];

        $cases = [
            'override enables www with inactive global setting' => [
                'http://example.com/a?b=1', ['example.com' => $base + ['wwwRedirect' => 'www']], false, '',
                'http://www.example.com/a?b=1'
            ],
            'override wins over opposite global setting' => [
                'http://www.example.com/a', ['example.com' => $base + ['wwwRedirect' => 'nonwww']], false, 'www',
                'http://example.com/a'
            ],
            'none disables global www redirect' => [
                'http://example.com/a', ['example.com' => $base + ['wwwRedirect' => 'none']], false, 'www', null
            ],
            'none keeps www variant during HTTPS redirect' => [
                'http://www.example.com/a', ['example.com' => $base + ['wwwRedirect' => 'none']], true, 'nonwww',
                'https://www.example.com/a'
            ],
            'empty override inherits global setting' => [
                'http://example.com/a', ['example.com' => $base + ['wwwRedirect' => '']], false, 'www',
                'http://www.example.com/a'
            ],
            'invalid stored override falls back to global setting' => [
                'http://example.com/a', ['example.com' => $base + ['wwwRedirect' => []]], false, 'www',
                'http://www.example.com/a'
            ],
            'counterpart of second VHost is recognized' => [
                'http://www.example.com:8080/a',
                ['unrelated.example' => $base, 'example.com' => $base + ['wwwRedirect' => 'nonwww']],
                false, '', 'http://example.com:8080/a'
            ],
            'canonical counterpart does not loop' => [
                'https://www.example.com/a', ['example.com' => $base + ['wwwRedirect' => 'www']], true, '', null
            ],
            'explicit counterpart overrides inferred variant' => [
                'http://www.example.com/a',
                ['example.com' => $base + ['wwwRedirect' => 'nonwww'],
                 'www.example.com' => $base + ['wwwRedirect' => 'none']],
                false, 'nonwww', null
            ],
            'explicit HTTPS host stays authoritative' => [
                'http://example.com/a',
                ['example.com' => $base + ['wwwRedirect' => 'www', 'httpshost' => 'secure.example.com']],
                true, '', 'https://secure.example.com/a'
            ],
            'wildcard override uses matched host' => [
                'http://www.shop.example.com/a', ['*.example.com' => $base + ['wwwRedirect' => 'nonwww']],
                false, '', 'http://shop.example.com/a'
            ],
            'IPv4 does not receive www prefix' => [
                'http://127.0.0.1:8080/a', ['127.0.0.1' => $base + ['wwwRedirect' => 'www']], false, '', null
            ],
            'IPv6 does not receive www prefix' => [
                'http://[::1]:8080/a', ['[::1]' => $base + ['wwwRedirect' => 'www']], false, '', null
            ],
            'localhost does not receive www prefix' => [
                'http://localhost/a', ['localhost' => $base + ['wwwRedirect' => 'www']], false, '', null
            ],
            'opposing explicit variants do not create a redirect loop' => [
                'http://example.com/a',
                ['example.com' => $base + ['wwwRedirect' => 'www'],
                 'www.example.com' => $base + ['wwwRedirect' => 'nonwww']],
                false, '', null
            ],
            'opposing explicit variants still enforce HTTPS' => [
                'http://example.com/a',
                ['example.com' => $base + ['wwwRedirect' => 'www'],
                 'www.example.com' => $base + ['wwwRedirect' => 'nonwww']],
                true, '', 'https://example.com/a'
            ],
            'conflicting configuration never reflects an unknown host' => [
                'http://attacker.test:8080/a',
                ['example.com' => $base + ['wwwRedirect' => 'www'],
                 'www.example.com' => $base + ['wwwRedirect' => 'nonwww']],
                true, '', 'https://example.com/a'
            ],
        ];

        foreach ($cases as $name => [$url, $hosts, $https, $global, $expected]) {
            yield $name => [$url, $hosts, $https, $global, '', '', $expected];
        }

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
