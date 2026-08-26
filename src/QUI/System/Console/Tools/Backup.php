<?php

/**
 * \QUI\System\Console\Tools\Backup
 */

namespace QUI\System\Console\Tools;

use QUI;
use Throwable;

/**
 * Class Backup
 * - Backup the complete system
 * - Backup the database
 * - Backup the filesystem
 */
class Backup extends QUI\System\Console\Tool
{
    private string $backupDirectory;

    /**
     * Cleanup constructor.
     */
    public function __construct(?string $backupDirectory = null)
    {
        $this->backupDirectory = rtrim($backupDirectory ?? VAR_DIR . 'backup/', '/\\') . DIRECTORY_SEPARATOR;
        $this->systemTool = true;

        $this->setName('quiqqer:backup')
            ->setDescription(
                'Create a backup of the system'
            )
            ->addArgument('help', 'Show this help', false, true)
            ->addArgument('type', 'The backup type (=full|db|filesystem)', 't', true);
    }

    public function execute(): void
    {
        $type = $this->getArgument('type');

        if (!$type) {
            $this->outputHelp();
            exit;
        }

        if ($type === 'full' || $type === 'db') {
            try {
                $this->createDatabaseBackup();
            } catch (QUI\Exception $Exception) {
                $this->writeLn($Exception->getMessage(), 'red');
                $this->resetColor();
            }
        }

        if ($type === 'full' || $type === 'filesystem') {
            try {
                $this->createFilesystemBackup();
            } catch (QUI\Exception $Exception) {
                $this->writeLn($Exception->getMessage(), 'red');
                $this->resetColor();
            }
        }
    }

    /**
     * @throws QUI\Exception
     */
    public function createDatabaseBackup(): void
    {
        $this->writeLn('Start database backup ...');

        $path = $this->backupDirectory;
        $driver = QUI::conf('db', 'driver');
        $host = QUI::conf('db', 'host');
        $database = QUI::conf('db', 'database');
        $user = QUI::conf('db', 'user');
        $password = QUI::conf('db', 'password');

        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }

        if ($driver === 'mysql') {
            $filename = $this->getAvailableBackupFilename($path, 'sql');
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s 2>/dev/null',
                escapeshellarg($user),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($filename)
            );

            system($command, $retVal);

            if ($retVal !== 0) {
                throw new QUI\Exception('Backup failed');
            }

            $this->writeLn('Database backup created: ' . $filename, 'green');
            $this->writeLn('');
            $this->resetColor();
            return;
        }

        if (in_array(strtolower((string)$driver), ['sqlite', 'sqlite3', 'pdo_sqlite'], true)) {
            $filename = $this->getAvailableBackupFilename($path, 'sqlite');

            try {
                $Connection = QUI::getDataBaseConnection();
                $Connection->executeStatement('VACUUM INTO ' . $Connection->quote($filename));
            } catch (Throwable $Exception) {
                throw new QUI\Exception('Backup failed: ' . $Exception->getMessage());
            }

            if (!is_file($filename)) {
                throw new QUI\Exception('Backup failed');
            }

            $this->writeLn('Database backup created: ' . $filename, 'green');
            $this->writeLn('');
            $this->resetColor();
            return;
        }

        throw new QUI\Exception('Unsupported DB driver: ' . $driver);
    }

    /**
     * @throws QUI\Exception
     */
    public function createFilesystemBackup(): void
    {
        $this->writeLn('Start filesystem backup ...');

        $path = $this->backupDirectory;
        $filename = $this->getAvailableBackupFilename($path, 'tar.gz');

        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }

        $base = rtrim(CMS_DIR, '/');
        $folders = ['etc', 'media', 'packages', 'usr', 'var'];
        $mainFiles = [
            '.htaccess',
            'ajax.php',
            'ajaxBundler.php',
            'bootstrap.php',
            'console',
            'image.php',
            'index.php',
            'quiqqer.php'
        ];
        $include = [];

        foreach ($folders as $folder) {
            $include[] = escapeshellarg($folder);
        }

        foreach ($mainFiles as $file) {
            $include[] = escapeshellarg($file);
        }

        // Exclude certain subfolders in var
        $excludedPaths = [
            'var/cache',
            'var/tmp',
            'var/uploads',
            'var/sessions',
            'var/backup'
        ];

        $driver = strtolower((string)QUI::conf('db', 'driver'));

        if (in_array($driver, ['sqlite', 'sqlite3', 'pdo_sqlite'], true)) {
            $databasePath = QUI::getDataBaseConnection()->getParams()['path'] ?? '';
            $basePrefix = $base . DIRECTORY_SEPARATOR;

            if (str_starts_with($databasePath, $basePrefix)) {
                $relativeDatabasePath = substr($databasePath, strlen($basePrefix));
                $excludedPaths[] = $relativeDatabasePath;
                $excludedPaths[] = $relativeDatabasePath . '-journal';
                $excludedPaths[] = $relativeDatabasePath . '-shm';
                $excludedPaths[] = $relativeDatabasePath . '-wal';
            }
        }

        $exclude = array_map(
            static fn(string $excludedPath): string => escapeshellarg('--exclude=' . $excludedPath),
            $excludedPaths
        );

        $command = sprintf(
            'cd %s && tar czf %s %s %s',
            escapeshellarg($base),
            escapeshellarg($filename),
            implode(' ', $exclude),
            implode(' ', $include)
        );

        system($command, $retVal);

        if ($retVal !== 0) {
            throw new QUI\Exception('Filesystem backup failed');
        }

        $this->writeLn('Filesystem backup created: ' . $filename, 'green');
        $this->resetColor();
    }

    private function getAvailableBackupFilename(string $path, string $extension): string
    {
        $baseFilename = $path . 'backup_' . date('Y_m_d__H_i_s');
        $filename = $baseFilename . '.' . $extension;
        $suffix = 1;

        while (file_exists($filename)) {
            $filename = $baseFilename . '_' . $suffix . '.' . $extension;
            $suffix++;
        }

        return $filename;
    }
}
