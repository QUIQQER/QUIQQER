<?php

declare(strict_types=1);

namespace QUI\System;

use PHPUnit\Framework\Attributes\DataProvider;
use QUI;
use QUI\Config;
use QUI\Projects\ProjectIntegrationTestCase;

final class VhostWwwRedirectTest extends ProjectIntegrationTestCase
{
    #[DataProvider('redirectModes')]
    public function testVhostStoresRedirectAndPreservesItWhenOmitted(string $mode): void
    {
        $Project = self::getTestProject();
        $file = tempnam(sys_get_temp_dir(), 'quiqqer-vhost-www-');
        self::assertNotFalse($file);
        $Manager = $this->createManager($file);

        try {
            $host = $Manager->addVhost('example.test');
            $data = ['project' => $Project->getName(), 'lang' => $Project->getLang()];
            $Manager->editVhost($host, $data + ['wwwRedirect' => $mode]);
            self::assertSame($mode, $Manager->getVhost($host)['wwwRedirect']);

            $Manager->editVhost($host, $data);
            self::assertSame($mode, $Manager->getVhost($host)['wwwRedirect']);
            self::assertSame($host, VhostManager::findVhost('www.example.test', $Manager->getList()));
            self::assertCount(1, $Manager->getList(), 'WWW variants share one project language assignment');

            $Manager->editVhost($host, $data + ['wwwRedirect' => '']);
            self::assertSame('', $Manager->getVhost($host)['wwwRedirect']);
        } finally {
            unlink($file);
        }
    }

    public function testInvalidRedirectIsRejectedWithoutChangingTheVhost(): void
    {
        $Project = self::getTestProject();
        $file = tempnam(sys_get_temp_dir(), 'quiqqer-vhost-www-');
        self::assertNotFalse($file);
        $Manager = $this->createManager($file);

        try {
            $host = $Manager->addVhost('example.test');
            $before = $Manager->getList();

            try {
                $Manager->editVhost($host, [
                    'project' => $Project->getName(),
                    'lang' => $Project->getLang(),
                    'wwwRedirect' => 'https://attacker.example'
                ]);
                self::fail('Invalid redirect must be rejected');
            } catch (QUI\Exception) {
                self::assertSame($before, $Manager->getList());
            }
        } finally {
            unlink($file);
        }
    }

    public function testGeneratedLanguageRouteUsesVhostWwwOverride(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'quiqqer-vhost-www-');
        self::assertNotFalse($file);
        $Config = new Config($file);

        try {
            $Config->setSection('example.test', [
                'project' => 'example', 'lang' => 'en', 'path_langs' => 'de', 'wwwRedirect' => 'www'
            ]);
            $Config->save();
            $Manager = $this->createManager($file);
            self::assertSame('www.example.test', $Manager->getProjectLanguageRoute('example', 'de')['host']);
            self::assertSame('de', $Manager->getProjectLanguageRoute('example', 'de')['path']);
        } finally {
            unlink($file);
        }
    }

    public function testOpposingWwwRedirectsAreRejectedBeforeSaving(): void
    {
        $Project = self::getTestProject();
        $file = tempnam(sys_get_temp_dir(), 'quiqqer-vhost-www-');
        self::assertNotFalse($file);
        $Config = new Config($file);

        try {
            $Config->setSection('example.test', ['wwwRedirect' => 'none']);
            $Config->setSection('www.example.test', ['wwwRedirect' => 'nonwww']);
            $Config->save();
            $Manager = $this->createManager($file);
            $before = $Manager->getList();

            try {
                $Manager->editVhost('example.test', [
                    'project' => $Project->getName(), 'lang' => $Project->getLang(), 'wwwRedirect' => 'www'
                ]);
                self::fail('Conflicting WWW redirects must be rejected');
            } catch (QUI\Exception) {
                self::assertSame($before, $Manager->getList());
            }
        } finally {
            unlink($file);
        }
    }

    public static function redirectModes(): iterable
    {
        foreach (['', 'www', 'nonwww', 'none'] as $mode) {
            yield [$mode];
        }
    }

    private function createManager(string $file): VhostManager
    {
        return new class ($file) extends VhostManager {
            public function __construct(private readonly string $file)
            {
            }

            protected function getConfig(): Config
            {
                return new Config($this->file);
            }
        };
    }
}
