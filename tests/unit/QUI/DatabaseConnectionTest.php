<?php

namespace QUI\Core;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use ReflectionMethod;
use ReflectionProperty;

use function bin2hex;
use function file_exists;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

class DatabaseConnectionTest extends TestCase
{
    private Config|null $previousConfig;
    private Connection|null $previousConnection;
    private ReflectionProperty $connectionProperty;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->previousConfig = QUI::$Conf;
        $this->connectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $this->previousConnection = $this->connectionProperty->getValue();
        $this->temporaryDirectory = sys_get_temp_dir() . '/quiqqer_sqlite_' . bin2hex(random_bytes(8));

        mkdir($this->temporaryDirectory, 0700, true);
    }

    protected function tearDown(): void
    {
        $Connection = $this->connectionProperty->getValue();

        if ($Connection instanceof Connection && $Connection !== $this->previousConnection) {
            $Connection->close();
        }

        $this->connectionProperty->setValue(null, $this->previousConnection);
        QUI::$Conf = $this->previousConfig;

        foreach (['quiqqer.sqlite', 'quiqqer.sqlite-journal', 'quiqqer.sqlite-shm', 'quiqqer.sqlite-wal'] as $file) {
            $path = $this->temporaryDirectory . '/' . $file;

            if (file_exists($path)) {
                unlink($path);
            }
        }

        if (is_dir($this->temporaryDirectory)) {
            rmdir($this->temporaryDirectory);
        }
    }

    public function testSqliteFactoryCreatesAndConfiguresFileDatabase(): void
    {
        $databasePath = $this->temporaryDirectory . '/quiqqer.sqlite';
        $Config = new Config();
        $Config->setSection('db', [
            'driver' => 'sqlite',
            'path' => $databasePath,
            'foreign_keys' => 1,
            'busy_timeout' => 2500,
            'journal_mode' => 'WAL',
            'synchronous' => 'NORMAL'
        ]);

        QUI::$Conf = $Config;
        $this->connectionProperty->setValue(null, null);

        $Connection = QUI::getDataBaseConnection();

        self::assertSame('pdo_sqlite', $Connection->getParams()['driver']);
        self::assertSame($databasePath, $Connection->getParams()['path']);
        self::assertFileExists($databasePath);
        self::assertSame(1, (int)$Connection->fetchOne('PRAGMA foreign_keys'));
        self::assertSame(2500, (int)$Connection->fetchOne('PRAGMA busy_timeout'));
        self::assertSame('wal', strtolower((string)$Connection->fetchOne('PRAGMA journal_mode')));
        self::assertSame(1, (int)$Connection->fetchOne('PRAGMA synchronous'));

        $Connection->executeStatement('CREATE TABLE factory_test (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        $Connection->insert('factory_test', ['value' => 'works']);

        self::assertSame($Connection, QUI::getDataBaseConnection());
        self::assertSame('works', QUI::getDataBaseConnection()->fetchOne('SELECT value FROM factory_test'));
    }

    public function testSqliteDefaultsUseEtcDatabaseDirectory(): void
    {
        $Method = new ReflectionMethod(QUI::class, 'getDoctrineConnectionParameters');
        $parameters = $Method->invoke(null, [
            'driver' => 'sqlite3',
            'database' => 'server_database_name_is_not_a_sqlite_path'
        ]);

        self::assertSame([
            'driver' => 'pdo_sqlite',
            'path' => ETC_DIR . 'database/quiqqer.sqlite'
        ], $parameters);
    }

    public function testRelativeSqlitePathIsResolvedFromInstallationDirectory(): void
    {
        $Method = new ReflectionMethod(QUI::class, 'getDoctrineConnectionParameters');
        $parameters = $Method->invoke(null, [
            'driver' => 'pdo_sqlite',
            'path' => 'etc/database/custom.sqlite'
        ]);

        self::assertSame(
            rtrim(CMS_DIR, '/\\') . '/etc/database/custom.sqlite',
            $parameters['path']
        );
    }

    public function testServerDatabaseParametersRemainCompatible(): void
    {
        $Method = new ReflectionMethod(QUI::class, 'getDoctrineConnectionParameters');
        $parameters = $Method->invoke(null, [
            'driver' => 'postgresql',
            'database' => 'quiqqer',
            'host' => 'database.example.test',
            'user' => 'quiqqer',
            'password' => 'secret'
        ]);

        self::assertSame('pdo_pgsql', $parameters['driver']);
        self::assertSame(5432, $parameters['port']);
        self::assertSame('quiqqer', $parameters['dbname']);
        self::assertSame('database.example.test', $parameters['host']);
        self::assertSame('quiqqer', $parameters['user']);
        self::assertSame('secret', $parameters['password']);
        self::assertFalse($parameters['persistent']);

        $mysqlParameters = $Method->invoke(null, [
            'driver' => 'mysql',
            'database' => 'quiqqer',
            'host' => 'mysql.example.test',
            'user' => 'quiqqer',
            'password' => 'secret'
        ]);

        self::assertSame('pdo_mysql', $mysqlParameters['driver']);
        self::assertSame(3306, $mysqlParameters['port']);
        self::assertSame('quiqqer', $mysqlParameters['dbname']);
        self::assertArrayNotHasKey('path', $mysqlParameters);
    }
}
