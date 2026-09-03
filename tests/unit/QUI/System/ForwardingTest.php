<?php

declare(strict_types=1);

namespace QUITests\System;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use QUI\System\Forwarding;
use Symfony\Component\HttpFoundation\Request;

class ForwardingTest extends TestCase
{
    private const CONFIG_KEY = 'etc/forwarding.ini.php';

    private string $configFile;

    private bool $hadPreviousConfig;

    private ?Config $PreviousConfig = null;

    protected function setUp(): void
    {
        $configFile = tempnam(sys_get_temp_dir(), 'quiqqer-forwarding-');
        self::assertNotFalse($configFile);
        $this->configFile = $configFile;
        $this->hadPreviousConfig = array_key_exists(self::CONFIG_KEY, QUI::$Configs);

        if ($this->hadPreviousConfig) {
            $this->PreviousConfig = QUI::$Configs[self::CONFIG_KEY];
        }

        QUI::$Configs[self::CONFIG_KEY] = new Config($this->configFile);
    }

    protected function tearDown(): void
    {
        if ($this->hadPreviousConfig && $this->PreviousConfig instanceof Config) {
            QUI::$Configs[self::CONFIG_KEY] = $this->PreviousConfig;
        } else {
            unset(QUI::$Configs[self::CONFIG_KEY]);
        }

        if (is_file($this->configFile)) {
            unlink($this->configFile);
        }
    }

    public function testCreateUpdateListAndSingleDeleteLifecycle(): void
    {
        Forwarding::create('https://example.test/old', 'https://example.test/new', 302);

        self::assertSame([
            'https://example.test/old' => [
                'target' => 'https://example.test/new',
                'code' => 302
            ]
        ], Forwarding::getList()->toArray());

        Forwarding::update('https://example.test/old', '/new-target', 307);
        self::assertSame([
            'target' => '/new-target',
            'code' => 307
        ], Forwarding::getList()->toArray()['https://example.test/old']);

        Forwarding::delete('https://example.test/old');
        self::assertSame([], Forwarding::getList()->toArray());
    }

    public function testEmptyHttpCodeUsesPermanentRedirectDefault(): void
    {
        Forwarding::create('/old', '/new', '');

        self::assertSame(301, Forwarding::getList()->toArray()['/old']['code']);
    }

    public function testDuplicateCreateIsRejected(): void
    {
        Forwarding::create('/duplicate', '/first');

        $this->expectException(QUI\Exception::class);
        Forwarding::create('/duplicate', '/second');
    }

    public function testUpdateOfUnknownSourceIsRejected(): void
    {
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(404);
        Forwarding::update('/missing', '/target');
    }

    public function testMultipleForwardingsCanBeDeletedTogether(): void
    {
        Forwarding::create('/one', '/target-one');
        Forwarding::create('/two', '/target-two');

        Forwarding::delete(['/one', '/two']);

        self::assertSame([], Forwarding::getList()->toArray());
    }

    public function testNonMatchingRequestIsNotForwarded(): void
    {
        Forwarding::create('https://example.test/match', '/target');

        Forwarding::forward(Request::create('https://example.test/no-match'));

        self::assertTrue(true);
    }

    public function testExactRequestCanBeResolvedWithoutRedirecting(): void
    {
        Forwarding::create('https://example.test/exact', '/target', 302);

        self::assertSame([
            'target' => '/target',
            'code' => 302
        ], Forwarding::resolve(Request::create('https://example.test/exact')));
    }

    public function testRequestWithTrailingSlashUsesTrimmedRule(): void
    {
        Forwarding::create('https://example.test', '/target', 301);

        self::assertSame([
            'target' => '/target',
            'code' => 301
        ], Forwarding::resolve(Request::create('https://example.test/')));
    }

    public function testWildcardRuleCanBeResolvedWithoutRedirecting(): void
    {
        Forwarding::create('https://example.test/legacy/*', '/target', 308);

        self::assertSame([
            'target' => '/target',
            'code' => 308
        ], Forwarding::resolve(Request::create('https://example.test/legacy/article')));
    }

    public function testRedirectResponseUsesConfiguredTargetAndCode(): void
    {
        $Response = Forwarding::createRedirectResponse([
            'target' => '/new-location',
            'code' => 308
        ]);

        self::assertSame('/new-location', $Response->getTargetUrl());
        self::assertSame(308, $Response->getStatusCode());
    }

    public function testRedirectResponseUsesCoreDefaultsForEmptyValues(): void
    {
        $Response = Forwarding::createRedirectResponse([
            'target' => '',
            'code' => 0
        ]);

        self::assertSame(URL_DIR, $Response->getTargetUrl());
        self::assertSame(301, $Response->getStatusCode());
    }
}
