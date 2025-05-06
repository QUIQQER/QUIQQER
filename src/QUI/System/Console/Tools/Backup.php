<?php

/**
 * \QUI\System\Console\Tools\Backup
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Class Backup
 * - Backup the complete system
 * - Backup the database
 * - Backup the filesystem
 */
class Backup extends QUI\System\Console\Tool
{
    /**
     * Cleanup constructor.
     */
    public function __construct()
    {
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

        $path = VAR_DIR . 'backup/';
        $driver = QUI::conf('db', 'driver');
        $host = QUI::conf('db', 'host');
        $database = QUI::conf('db', 'database');
        $user = QUI::conf('db', 'user');
        $password = QUI::conf('db', 'password');

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $filename = $path . 'backup_' . date('Y_m_d__H_i_s') . '.sql';

        if ($driver === 'mysql') {
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
        } else {
            throw new QUI\Exception('Unsupported DB driver: ' . $driver);
        }
    }

    /**
     * @throws QUI\Exception
     */
    public function createFilesystemBackup(): void
    {
        $this->writeLn('Start filesystem backup ...');

        $path = VAR_DIR . 'backup/';
        $filename = $path . 'backup_' . date('Y_m_d__H_i_s') . '.tar.gz';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $base = rtrim(CMS_DIR, '/');
        $folders = ['etc', 'media', 'packages', 'usr', 'var'];
        $mainFiles = [
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
        $exclude = [
            '--exclude=var/cache',
            '--exclude=var/tmp',
            '--exclude=var/uploads',
            '--exclude=var/sessions',
            '--exclude=var/backup'
        ];

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
}
