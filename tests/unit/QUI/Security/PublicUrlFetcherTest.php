<?php

namespace QUI\Security;

use PHPUnit\Framework\TestCase;
use QUI\Exception;

require_once __DIR__ . '/Fixtures/PublicUrlFetcherTestDouble.php';

class PublicUrlFetcherTest extends TestCase
{
    public function testRejectsNonHttpSchemesAndCredentials(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble();

        foreach (
            [
                'file:///etc/passwd',
                'data:text/plain,secret',
                'ftp://example.test/file',
                'http://user:secret@example.test/image.png'
            ] as $url
        ) {
            try {
                $Fetcher->get($url);
                self::fail('Unsafe URL was accepted: ' . $url);
            } catch (Exception) {
                self::assertSame([], $Fetcher->requests);
            }
        }
    }

    public function testRejectsLiteralLocalAndPrivateAddresses(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble();

        foreach (
            [
                'http://127.0.0.1/secret',
                'http://10.10.10.10/secret',
                'http://100.64.0.1/secret',
                'http://169.254.169.254/latest/meta-data/',
                'http://224.0.0.1/secret',
                'http://[::1]/secret',
                'http://[fc00::1]/secret',
                'http://[64:ff9b::7f00:1]/secret',
                'http://[ff02::1]/secret'
            ] as $url
        ) {
            try {
                $Fetcher->get($url);
                self::fail('Private destination was accepted: ' . $url);
            } catch (Exception) {
                self::assertSame([], $Fetcher->requests);
            }
        }
    }

    public function testRejectsPortsOutsideValidRange(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble();

        foreach (['http://example.test:0/image.png', 'http://example.test:65536/image.png'] as $url) {
            try {
                $Fetcher->get($url);
                self::fail('Invalid port was accepted: ' . $url);
            } catch (Exception) {
                self::assertSame([], $Fetcher->requests);
            }
        }
    }

    public function testRejectsHostnameThatOnlyResolvesPrivately(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble([
            'internal.example.test' => ['127.0.0.1', '10.0.0.8', '::1']
        ]);

        $this->expectException(Exception::class);
        $Fetcher->get('https://internal.example.test/secret');
    }

    public function testRejectsObfuscatedLoopbackAddressAfterResolution(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble([
            '2130706433' => ['127.0.0.1']
        ]);

        $this->expectException(Exception::class);
        $Fetcher->get('http://2130706433/secret');
    }

    public function testPinsRequestToPublicResolvedAddress(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble([
            'images.example.test' => ['10.0.0.10', '93.184.216.34']
        ], [
            [
                'status' => 200,
                'location' => null,
                'body' => 'image-bytes'
            ]
        ]);

        self::assertSame('image-bytes', $Fetcher->get('https://images.example.test/picture.png?size=large'));
        self::assertSame([
            [
                'url' => 'https://images.example.test/picture.png?size=large',
                'host' => 'images.example.test',
                'port' => 443,
                'addresses' => ['93.184.216.34']
            ]
        ], $Fetcher->requests);
    }

    public function testRevalidatesRedirectDestination(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble([
            'images.example.test' => ['93.184.216.34'],
            'metadata.example.test' => ['169.254.169.254']
        ], [
            [
                'status' => 302,
                'location' => 'http://metadata.example.test/latest/meta-data/',
                'body' => ''
            ]
        ]);

        try {
            $Fetcher->get('https://images.example.test/redirect');
            self::fail('Redirect to private destination was accepted.');
        } catch (Exception) {
            self::assertCount(1, $Fetcher->requests);
        }
    }

    public function testFollowsRelativeRedirectAfterRevalidation(): void
    {
        $Fetcher = new PublicUrlFetcherTestDouble([
            'images.example.test' => ['93.184.216.34']
        ], [
            [
                'status' => 302,
                'location' => '../final/image.png',
                'body' => ''
            ],
            [
                'status' => 200,
                'location' => null,
                'body' => 'safe-image'
            ]
        ]);

        self::assertSame('safe-image', $Fetcher->get('https://images.example.test/path/redirect'));
        self::assertSame('https://images.example.test/final/image.png', $Fetcher->requests[1]['url']);
    }
}
