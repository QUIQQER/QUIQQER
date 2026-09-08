<?php

declare(strict_types=1);

namespace QUI;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SettingsAjaxSecurityTest extends TestCase
{
    public function testAliasedCoreConfigPathRequiresNonceWhenSaving(): void
    {
        \QUI::$Ajax = new Ajax();

        $coreDirectory = OPT_DIR . 'quiqqer/core';
        require $coreDirectory . '/admin/ajax/settings/save.php';

        $callable = Ajax::getRegisteredCallables()['ajax_settings_save']['callable'];
        $aliasedConfigFile = $coreDirectory . '/admin/settings/../settings/conf.xml';

        self::assertStringNotContainsString(
            'quiqqer/core/admin/settings/conf.xml',
            $aliasedConfigFile
        );
        self::assertSame(
            realpath($coreDirectory . '/admin/settings/conf.xml'),
            realpath($aliasedConfigFile)
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not save QUIQQER config');

        $callable(
            json_encode($aliasedConfigFile, JSON_THROW_ON_ERROR),
            json_encode([], JSON_THROW_ON_ERROR)
        );
    }

    public function testAliasedCoreConfigPathKeepsSensitiveSettingsHidden(): void
    {
        \QUI::$Ajax = new Ajax();

        $Config = \QUI::getConfig('etc/conf.ini.php');
        $previousLocks = $Config->get('locks');
        $Config->setValue('locks', 'dsn', 'redis://alice:private-lock-password@localhost/2');
        $Config->save();

        $coreDirectory = OPT_DIR . 'quiqqer/core';
        require $coreDirectory . '/admin/ajax/settings/get.php';

        $callable = Ajax::getRegisteredCallables()['ajax_settings_get']['callable'];
        $aliasedConfigFile = $coreDirectory . '/admin/settings/../settings/conf.xml';

        self::assertStringNotContainsString(
            'quiqqer/core/admin/settings/conf.xml',
            $aliasedConfigFile
        );
        self::assertSame(
            realpath($coreDirectory . '/admin/settings/conf.xml'),
            realpath($aliasedConfigFile)
        );

        try {
            $config = $callable(json_encode($aliasedConfigFile, JSON_THROW_ON_ERROR));
        } finally {
            $Config->set('locks', is_array($previousLocks) ? $previousLocks : []);
            $Config->save();
        }

        self::assertArrayHasKey('globals', $config);
        self::assertArrayNotHasKey('db', $config);
        self::assertArrayNotHasKey('openssl', $config);
        self::assertArrayNotHasKey('locks', $config);
        self::assertStringNotContainsString('private-lock-password', json_encode($config));
        self::assertArrayNotHasKey('salt', $config['globals']);
        self::assertArrayNotHasKey('saltlength', $config['globals']);
        self::assertArrayNotHasKey('cms_dir', $config['globals']);
        self::assertArrayNotHasKey('var_dir', $config['globals']);
        self::assertArrayNotHasKey('usr_dir', $config['globals']);
        self::assertArrayNotHasKey('opt_dir', $config['globals']);
        self::assertArrayNotHasKey('rootuser', $config['globals']);
        self::assertArrayNotHasKey('root', $config['globals']);
    }
}
