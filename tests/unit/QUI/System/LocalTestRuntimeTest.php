<?php

namespace QUITests\QUI\System;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\TestCase;
use QUI;
use QUITests\Support\DatabaseEnvironment;

final class LocalTestRuntimeTest extends TestCase
{
    public function testDatabaseSelectionRequiresExplicitCi(): void
    {
        foreach (
            [[], ['CI' => 'true'], ['GITLAB_CI' => 'false'],
            ['QUIQQER_IP_THROTTLE_TEST_DATABASE' => '{"driver":"pdo_mysql"}']] as $environment
        ) {
            self::assertSame(DatabaseEnvironment::MODE_SQLITE, DatabaseEnvironment::determineMode($environment));
        }
        self::assertSame(DatabaseEnvironment::MODE_CI_DATABASE, DatabaseEnvironment::determineMode(['GITLAB_CI' => 'true']));
    }

    public function testRuntimeWritesStayWithinTheSelectedInstallation(): void
    {
        $source = dirname(__DIR__, 7) . '/';
        if (DatabaseEnvironment::usesCiDatabase()) {
            self::assertSame($source, CMS_DIR);
            self::assertSame($source . 'etc/', ETC_DIR);
            return;
        }

        self::assertInstanceOf(SQLitePlatform::class, QUI::getDataBaseConnection()->getDatabasePlatform());
        self::assertNotSame($source, CMS_DIR);
        self::assertSame(0700, fileperms(CMS_DIR) & 0777);
        self::assertSame(CMS_DIR . 'etc/', ETC_DIR);
        self::assertSame(CMS_DIR . 'var/', VAR_DIR);
        self::assertSame(CMS_DIR . 'usr/', USR_DIR);
        self::assertSame(VAR_DIR . 'bootstrap.sqlite', QUI::getDataBaseConnection()->getParams()['path']);
        $originalHash = hash_file('sha256', $source . 'etc/conf.ini.php');
        $Config = new QUI\Config(ETC_DIR . 'conf.ini.php');
        $Config->setValue('phpunit', 'isolation', 'local');
        $Config->save();
        self::assertSame($originalHash, hash_file('sha256', $source . 'etc/conf.ini.php'));
        self::assertArrayNotHasKey('host', QUI::getDataBaseConnection()->getParams());
    }

    public function testChildUsesTheSameDatabaseAndCannotCleanUpItsParent(): void
    {
        $key = bin2hex(random_bytes(24));
        $Connection = QUI::getDataBaseConnection();
        $Connection->insert(QUI\Security\Throttle::table(), [
            'throttleKey' => $key, 'package' => 'quiqqer/core', 'subjectKey' => $key,
            'reservationId' => '', 'attempts' => 1, 'expiresAt' => time() + 60
        ]);
        try {
            $Process = proc_open([
                PHP_BINARY, dirname(__DIR__, 3) . '/fixtures/runtime-child.php.fixture', $key
            ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($Process);
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($Process), $errors . $output);
            self::assertSame(['root' => CMS_DIR, 'attempts' => 1], json_decode($output, true, flags: JSON_THROW_ON_ERROR));
            self::assertSame(2, (int)$Connection->fetchOne(
                'SELECT attempts FROM ' . QUI\Security\Throttle::table() . ' WHERE throttleKey = ?',
                [$key]
            ));
            self::assertDirectoryExists(CMS_DIR);
            self::assertFileExists(ETC_DIR . 'conf.ini.php');
        } finally {
            $Connection->delete(QUI\Security\Throttle::table(), ['throttleKey' => $key]);
        }
    }
}
