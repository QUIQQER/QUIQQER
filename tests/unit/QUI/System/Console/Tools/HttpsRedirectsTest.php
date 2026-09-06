<?php

declare(strict_types=1);

namespace QUITests\QUI\System\Console\Tools;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Rewrite;
use QUI\System\Console\Tools\HttpsRedirects;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class HttpsRedirectsTest extends TestCase
{
    /**
     * @param array<string, array<string, mixed>> $vhosts
     */
    #[DataProvider('redirects')]
    public function testGeneratedRedirectMatchesPhp(
        array $vhosts,
        string $wwwRedirect,
        string $host,
        string $expected,
        string $globalHost = '',
        string $globalHttpsHost = ''
    ): void {
        $Redirects = new HttpsRedirects($vhosts, true, $wwwRedirect, $globalHost, $globalHttpsHost);
        $uri = '/de/a%20b/%C3%A4?return=%2Fa%3Fb&x=1';
        $expected = 'https://' . $expected . $uri;

        self::assertSame($expected, self::apacheLocation($Redirects->apache(), $host, $uri));
        self::assertSame(
            $expected,
            Rewrite::getCanonicalHostRedirectUrl(
                Request::create('http://' . $host . $uri),
                $vhosts,
                true,
                $wwwRedirect,
                $globalHost,
                $globalHttpsHost
            )
        );

        self::assertStringContainsString('$request_uri', $Redirects->nginx());
        self::assertStringContainsString('{uri} permanent', $Redirects->caddy());
    }

    public function testDisabledHttpsDoesNotGenerateRedirects(): void
    {
        $Redirects = new HttpsRedirects(
            ['example.com' => ['httpshost' => 'secure.example.com']],
            false,
            'www',
            '',
            ''
        );

        self::assertSame('', $Redirects->apache());
        self::assertSame('', $Redirects->nginx());
        self::assertSame('', $Redirects->caddy());
    }

    #[DataProvider('invalidHosts')]
    public function testInvalidHostCannotInjectServerConfiguration(string $host): void
    {
        $Redirects = new HttpsRedirects(['example.com' => ['httpshost' => $host]], true, '', '', '');
        $this->expectException(UnexpectedValueException::class);
        $Redirects->apache();
    }

    public static function invalidHosts(): iterable
    {
        yield ['example.com;return'];
        yield ['${host}.example.com'];
        yield ['{host}.example.com'];
        yield ["example.com\nRewriteRule"];
        yield ['example.com"'];
    }

    public static function redirects(): iterable
    {
        yield 'configured HTTPS host' => [
            ['example.com' => ['httpshost' => 'www.example.com']], '', 'example.com', 'www.example.com'
        ];
        yield 'HTTPS alias' => [
            ['example.com' => ['httpshost' => 'secure.example.com']], '', 'secure.example.com', 'secure.example.com'
        ];
        yield 'second VHost has its own target' => [
            ['example.com' => ['httpshost' => 'www.example.com'],
             'example.org' => ['httpshost' => 'secure.example.org']],
            '', 'example.org', 'secure.example.org'
        ];
        yield 'WWW counterpart of second VHost' => [
            ['example.com' => [], 'example.org' => ['httpshost' => 'secure.example.org']],
            '', 'www.example.org', 'secure.example.org'
        ];
        yield 'explicit host wins over alias' => [
            ['example.com' => ['httpshost' => 'example.org'],
             'example.org' => ['httpshost' => 'secure.example.org']],
            '', 'example.org', 'secure.example.org'
        ];
        yield 'WWW and HTTPS in one step' => [
            ['example.com' => []], 'www', 'example.com', 'www.example.com'
        ];
        yield 'remove WWW and preserve port' => [
            ['example.com' => ['wwwRedirect' => 'nonwww']], 'www', 'www.example.com:8080', 'example.com:8080'
        ];
        yield 'default HTTP port is not carried into HTTPS' => [
            ['example.com' => []], 'www', 'example.com:80', 'www.example.com'
        ];
        yield 'disabled WWW override' => [
            ['example.com' => ['wwwRedirect' => 'none']], 'www', 'example.com', 'example.com'
        ];
        yield 'HTTPS host overrides WWW setting' => [
            ['example.com' => ['httpshost' => 'secure.example.com']], 'www', 'example.com', 'secure.example.com'
        ];
        yield 'configured target port replaces incoming port' => [
            ['example.com' => ['httpshost' => 'https://secure.example.com:8443/']],
            '', 'example.com:8080', 'secure.example.com:8443'
        ];
        yield 'opposing WWW settings avoid loops' => [
            ['example.com' => ['wwwRedirect' => 'www'], 'www.example.com' => ['wwwRedirect' => 'nonwww']],
            '', 'example.com', 'example.com'
        ];
        yield 'unknown host uses configured fallback' => [
            ['example.com' => ['httpshost' => 'secure.example.com']], '', 'unknown.invalid', 'secure.example.com'
        ];
        yield 'global fallback without VHosts' => [
            [], '', 'example.com', 'secure.example.com', 'http://example.com/', 'https://secure.example.com/'
        ];
        yield 'global host preserves custom port' => [
            [], '', 'example.com:8080', 'example.com:8080', 'http://example.com/', ''
        ];
        yield 'wildcard keeps subdomain' => [
            ['*.example.com' => []], '', 'shop.example.com', 'shop.example.com'
        ];
        yield 'wildcard targets concrete HTTPS host' => [
            ['*.example.com' => ['httpshost' => 'secure.example.com']],
            '', 'shop.example.com', 'secure.example.com'
        ];
        yield 'concrete host wins over wildcard' => [
            ['*.example.com' => ['httpshost' => 'secure.example.com'], 'shop.example.com' => ['wwwRedirect' => 'none']],
            '', 'shop.example.com', 'shop.example.com'
        ];
        yield 'wildcard removes WWW' => [
            ['*.example.com' => ['wwwRedirect' => 'nonwww']], '', 'www.shop.example.com', 'shop.example.com'
        ];
        yield 'wildcard adds WWW' => [
            ['*.example.com' => ['wwwRedirect' => 'www']], '', 'shop.example.com:8080', 'www.shop.example.com:8080'
        ];
        yield 'wildcard does not preserve default HTTP port' => [
            ['*.example.com' => ['wwwRedirect' => 'www']], '', 'shop.example.com:80', 'www.shop.example.com'
        ];
        yield 'wildcard without WWW does not preserve default HTTP port' => [
            ['*.example.com' => []], '', 'shop.example.com:80', 'shop.example.com'
        ];
        yield 'wildcard does not add WWW twice' => [
            ['*.example.com' => ['wwwRedirect' => 'www']], '', 'www.shop.example.com', 'www.shop.example.com'
        ];
        yield 'matching HTTPS wildcard stays authoritative' => [
            ['*.example.com' => ['httpshost' => '*.example.com', 'wwwRedirect' => 'www']],
            '', 'shop.example.com:8080', 'shop.example.com'
        ];
        yield 'different HTTPS wildcard does not become literal target' => [
            ['*.example.com' => ['httpshost' => '*.example.org']], '', 'shop.example.com', 'shop.example.com'
        ];
        yield 'HTTPS wildcard alias' => [
            ['example.com' => ['httpshost' => '*.example.org']], '', 'shop.example.org', 'shop.example.org'
        ];
        yield 'case insensitive host' => [
            ['example.com' => ['httpshost' => 'secure.example.com']], '', 'EXAMPLE.COM', 'secure.example.com'
        ];
        yield 'IPv4 has no WWW prefix' => [
            ['127.0.0.1' => []], 'www', '127.0.0.1:8080', '127.0.0.1:8080'
        ];
        yield 'IPv6 host and port' => [
            ['[::1]' => []], 'www', '[::1]:8080', '[::1]:8080'
        ];
    }

    private static function apacheLocation(string $config, string $host, string $uri): ?string
    {
        $matched = true;
        $captures = [];

        foreach (explode("\n", $config) as $line) {
            $line = trim($line);

            if ($line === 'RewriteCond %{HTTPS} !on') {
                $matched = true;
                $captures = [];
            } elseif (preg_match('/^RewriteCond %\{HTTP_HOST\} (.+) \[NC\]$/', $line, $parts)) {
                $matched = $matched && preg_match('~' . $parts[1] . '~i', $host, $captures) === 1;
            } elseif ($matched && preg_match('/^RewriteRule \^ (https:\/\/\S+) \[/', $line, $parts)) {
                return str_replace(
                    ['%1', '%{HTTP_HOST}', '%{ENV:QUIQQER_HTTPS_PATH}'],
                    [$captures[1] ?? '', $host, $uri],
                    $parts[1]
                );
            }
        }

        return null;
    }
}
