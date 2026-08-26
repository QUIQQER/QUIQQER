<?php

namespace QUI\System\Console\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use ReflectionProperty;

use function bin2hex;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sort;
use function sys_get_temp_dir;
use function unlink;

class BackupSqliteTest extends TestCase
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
        $this->temporaryDirectory = sys_get_temp_dir() . '/quiqqer_backup_' . bin2hex(random_bytes(8));

        mkdir($this->temporaryDirectory, 0700, true);

        $Config = new Config();
        $Config->setSection('db', [
            'driver' => 'sqlite',
            'path' => $this->temporaryDirectory . '/source.sqlite',
            'journal_mode' => 'WAL'
        ]);

        QUI::$Conf = $Config;
        $this->connectionProperty->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $Connection = $this->connectionProperty->getValue();

        if ($Connection instanceof Connection && $Connection !== $this->previousConnection) {
            $Connection->close();
        }

        $this->connectionProperty->setValue(null, $this->previousConnection);
        QUI::$Conf = $this->previousConfig;

        if (is_dir($this->temporaryDirectory)) {
            $this->deleteDirectory($this->temporaryDirectory);
        }
    }

    public function testCreatesConsistentSqliteBackupTwice(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $Connection->executeStatement('CREATE TABLE backup_test (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        $Connection->insert('backup_test', ['value' => 'first']);

        $backupDirectory = $this->temporaryDirectory . '/backups';
        $Backup = new Backup($backupDirectory);

        $Backup->createDatabaseBackup();
        $Connection->insert('backup_test', ['value' => 'second']);
        $Backup->createDatabaseBackup();

        $backupFiles = [];

        foreach (new FilesystemIterator($backupDirectory, FilesystemIterator::SKIP_DOTS) as $File) {
            $backupFiles[] = $File->getPathname();
        }

        sort($backupFiles);

        self::assertCount(2, $backupFiles);
        self::assertNotSame($backupFiles[0], $backupFiles[1]);
        self::assertSame(1, $this->getBackupRowCount($backupFiles[0]));
        self::assertSame(2, $this->getBackupRowCount($backupFiles[1]));
    }

    private function getBackupRowCount(string $databasePath): int
    {
        $Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $databasePath
        ]);

        try {
            return (int)$Connection->fetchOne('SELECT COUNT(*) FROM backup_test');
        } finally {
            $Connection->close();
        }
    }

    private function deleteDirectory(string $directory): void
    {
        foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $File) {
            if ($File->isDir()) {
                $this->deleteDirectory($File->getPathname());
                continue;
            }

            unlink($File->getPathname());
        }

        rmdir($directory);
    }
}
