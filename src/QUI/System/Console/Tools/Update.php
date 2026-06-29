<?php

/**
 * This file contains QUI\System\Console\Tools\Update
 */

namespace QUI\System\Console\Tools;

use Exception;
use QUI;

use function count;
use function date;
use function error_log;
use function explode;
use function function_exists;
use function implode;
use function is_dir;
use function is_resource;
use function method_exists;
use function preg_replace;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function str_pad;
use function str_replace;
use function strip_tags;
use function strlen;
use function strtolower;
use function system;
use function trim;
use function unlink;

use const CMS_DIR;
use const PHP_EOL;
use const VAR_DIR;

/**
 * Update command for the console
 */
class Update extends QUI\System\Console\Tool
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->systemTool = true;

        $this->setName('quiqqer:update')
            ->setDescription('Update the quiqqer system and the quiqqer packages')
            ->addArgument(
                'clearCache',
                QUI::getLocale()->get('quiqqer/core', 'console.update.clearCache'),
                false,
                true
            )
            ->addArgument(
                'setDevelopment',
                QUI::getLocale()->get('quiqqer/core', 'console.update.setDevelopment'),
                false,
                true
            )->addArgument(
                'check',
                QUI::getLocale()->get('quiqqer/core', 'console.update.check'),
                false,
                true
            )->addArgument(
                'set-date',
                QUI::getLocale()->get('quiqqer/core', 'console.update.set-date'),
                false,
                true
            )->addArgument(
                'package',
                QUI::getLocale()->get('quiqqer/core', 'console.update.package.update.check'),
                false,
                true
            )->addArgument(
                'skip-filesystem-check',
                QUI::getLocale()->get('quiqqer/core', 'console.update.skip-filesystem-check'),
                false,
                true
            )->addArgument(
                'verbose',
                'Show verbose update progress output',
                'v',
                true
            )->addArgument(
                'cancel',
                'Cancel an active update run by run id',
                false,
                true
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        if ($this->getArgument('cancel')) {
            $this->cancelUpdateRun((string)$this->getArgument('cancel'));
            exit(0);
        }

        if ($this->getArgument('set-date')) {
            $this->executeSystemUpdate();
            return;
        }

        $this->launchUpdateRun();
    }

    public function executeSystemUpdate(): bool
    {
        $this->writeUpdateLog('====== EXECUTE UPDATE ======');
        $this->writeUpdateLog(QUI::getLocale()->get('quiqqer/core', 'update.log.message.execute.console'));

        Cleanup::clearComposer();

        $this->writeLn(QUI::getLocale()->get('quiqqer/core', 'update.message.start'));

        // check license
        try {
            $licenceData = QUI\System\License::getLicenseData();

            if ($licenceData) {
                $status = QUI\System\License::getStatus();

                if ($status && isset($status['active']) && $status['active'] === false) {
                    $message = QUI::getLocale()->get('quiqqer/core', 'update.log.message.licenseActivation');
                    $message = preg_replace('#([ ]){2,}#', "$1", $message);
                    $message = str_replace(PHP_EOL . " ", PHP_EOL, $message);
                    $message = trim($message);

                    $this->writeLn();
                    $this->writeLn($message, 'red');
                    $this->writeLn();
                    $this->writeLn();
                    $this->resetColor();
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->writeLn($e->getMessage(), 'red');
            $this->resetColor();
        }

        $Packages = QUI::getPackageManager();

        // output events
        $Packages->getComposer()->addEvent('onOutput', function ($Composer, $output, $type): void {
            if ($this->getArgument('check') && $this->getVerbosityLevel() === 0) {
                return;
            }

            $this->write($output);
            self::writeToLog($output);
        });

        if ($this->getArgument('set-date')) {
            try {
                QUI::getPackageManager()->setLastUpdateDate();
            } catch (QUI\Exception $Exception) {
                self::writeToLog('====== ERROR ======');
                self::writeToLog($Exception->getMessage());

                return false;
            }

            return true;
        }

        if ($this->getArgument('clearCache')) {
            try {
                $Packages->clearComposerCache();
            } catch (QUI\Exception $Exception) {
                self::writeToLog('====== ERROR ======');
                self::writeToLog($Exception->getMessage());

                return false;
            }
        }

        if ($this->getArgument('check')) {
            $this->writeLn(QUI::getLocale()->get('quiqqer/core', 'update.log.message.update.via.console'));
            $this->writeLn();
            $this->writeLn();

            if ($this->getVerbosityLevel() > 0) {
                $Packages->getComposer()->unmute();
            }

            try {
                $packages = $Packages->getOutdated(true, $this->getComposerVerbosityOptions());
            } catch (Exception $Exception) {
                self::writeToLog('====== ERROR ======');
                self::writeToLog($Exception->getMessage());

                return false;
            }

            $nameLength = 0;
            $versionLength = 0;

            // #locale
            if (empty($packages)) {
                $this->writeLn(
                    QUI::getLocale()->get('quiqqer/core', 'update.message.no.updates.available'),
                    'green'
                );

                return true;
            }

            foreach ($packages as $package) {
                if (strlen($package['package']) > $nameLength) {
                    $nameLength = strlen($package['package']);
                }

                if (strlen($package['oldVersion']) > $versionLength) {
                    $versionLength = strlen($package['oldVersion']);
                }
            }

            foreach ($packages as $package) {
                $this->write(
                    str_pad($package['package'], $nameLength + 2),
                    'green'
                );

                $this->resetColor();
                $this->write(
                    str_pad($package['oldVersion'], $versionLength + 2) . ' -> '
                );

                $this->write($package['version'], 'cyan');
                $this->writeLn();
            }

            return true;
        }

        $Maintenance = new Maintenance();
        $Maintenance->setArgument('status', 'on');
        $Maintenance->execute();

        $executeFileSystemCheck = true;
        if ($this->getArgument('skip-filesystem-check')) {
            $executeFileSystemCheck = false;
        }

        if ($executeFileSystemCheck) {
            try {
                $this->writeLn('- File system is checked for changes ...');
                $changes = $this->checkFileSystemChanges();
            } catch (Exception $Exception) {
                $this->writeLn();
                $this->writeLn(
                    'The update has received inconsistencies during the file system check.',
                    'yellow'
                );

                $this->writeLn('Error :: ' . $Exception->getMessage(), 'red');
                $this->writeLn();
                $this->resetColor();

                if ($this->executedAnywayQuestion() === false) {
                    $Maintenance->setArgument('status', 'off');
                    $Maintenance->execute();
                    return false;
                }

                $changes = false;
            }

            if ($changes) {
                $this->writeLn();
                $this->writeLn('The update has found inconsistencies in the system!', 'yellow');
                $this->resetColor();

                if ($this->executedAnywayQuestion() === false) {
                    $Maintenance->setArgument('status', 'off');
                    $Maintenance->execute();
                    return false;
                }
            }
        }

        // init backup
        $etcBackupFolder = QUI\System\Backup::createEtcBackup();

        // start update routines
        $CLIOutput = new QUI\System\Console\Output();
        $CLIOutput->Events->addEvent('onWrite', function ($message): void {
            self::onCliOutput($message, $this);
        });

        try {
            $Packages->refreshServerList();

            $Composer = $Packages->getComposer();
            $Composer->unmute();
            $Composer->setOutput($CLIOutput);

            if ($this->getArgument('package')) {
                $this->writeLn('Update Package ' . $this->getArgument('package') . '...');

                $Composer->update([
                    'packages' => [
                        $this->getArgument('package')
                    ],
                    '--with-dependencies' => false,
                    '--no-autoloader' => false,
                    '--optimize-autoloader' => true
                ]);
            } else {
                $localeDir = VAR_DIR . 'locale/';
                $localeFiles = $localeDir . 'localefiles';
                $entries = QUI\Utils\System\File::readDir($localeDir);
                $oldDirsAvailable = false;

                // cleanup
                foreach ($entries as $entry) {
                    if ($entry === 'localefiles' || $entry === 'bin') {
                        continue;
                    }

                    // delete old dirs
                    if (is_dir($localeDir . $entry) && str_contains($entry, '_')) {
                        QUI\Utils\System\File::deleteDir($localeDir . $entry);
                        $oldDirsAvailable = true;
                    }
                }

                if ($oldDirsAvailable) {
                    unlink($localeFiles);
                }

                $this->writeLn('QUIQQER Update ...');
                $Packages->getComposer()->setOutput($CLIOutput);
                $Packages->update(false, false, $this);
            }

            $wasExecuted = QUI::getLocale()->get('quiqqer/core', 'update.message.execute');
            $webserver = QUI::getLocale()->get('quiqqer/core', 'update.message.webserver');

            $this->writeLn($wasExecuted);
            self::writeToLog($wasExecuted . PHP_EOL);

            $this->writeLn($webserver);
            self::writeToLog($webserver . PHP_EOL);

            $Htaccess = new Htaccess();
            $Htaccess->execute();

            $NGINX = new Nginx();
            $NGINX->execute();

            $Frankenphp = new Frankenphp();
            $Frankenphp->execute();

            self::writeToLog(PHP_EOL);
            self::writeToLog('✔️' . PHP_EOL);
            self::writeToLog(PHP_EOL);

            // setup set the last update date
            QUI::getPackageManager()->setLastUpdateDate();

            QUI\Cache\Manager::clearCompleteQuiqqerCache();
            QUI\Cache\Manager::longTimeCacheClearCompleteQuiqqer();

            // check init backup, with current inits
            $diff = QUI\System\Backup::diff($etcBackupFolder);

            if (!empty($diff)) {
                $this->write($diff);

                $this->writeLn('There have been changes to the ini files!', 'blue');
                $this->writeLn('Should the etc backup be deleted anyway? [Y,n]', 'blue');
                $this->resetColor();
                $input = $this->readInput();

                if (strtolower($input) === 'y') {
                    QUI\System\Backup::deleteEtcBackup($etcBackupFolder);
                }
            } else {
                QUI\System\Backup::deleteEtcBackup($etcBackupFolder);
            }
        } catch (Exception $Exception) {
            $this->write(' [error]', 'red');
            $this->writeLn();
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/core', 'update.message.error.1') . '::' . $Exception->getMessage(),
                'red'
            );

            if ($Exception instanceof QUI\Exception) {
                QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());
            }

            $this->writeLn(
                QUI::getLocale()->get('quiqqer/core', 'update.message.error'),
                'red'
            );

            $this->writeLn();
            $this->writeLn('./console repair', 'red');
            $this->resetColor();
            $this->writeLn();

            $Maintenance->setArgument('status', 'off');
            $Maintenance->execute();

            return false;
        }

        $Maintenance->setArgument('status', 'off');
        $Maintenance->execute();

        return true;
    }

    protected function launchUpdateRun(): void
    {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');
        $runs = $Repository->cleanupAndFindActive(time(), 86400);

        foreach ($runs['deleted'] as $id) {
            $this->writeLn('Removed stale update run: ' . $id, 'yellow');
        }

        if (!empty($runs['active'])) {
            $lines = [
                'Update already running',
                'Another update process is active.',
                'Wait until it has finished or cancel the active run.',
                ''
            ];

            foreach ($runs['active'] as $index => $State) {
                if ($index > 0) {
                    $lines[] = '';
                }

                $process = $State->getProcess();
                $pid = is_array($process) ? (int)($process['pid'] ?? 0) : 0;

                $lines[] = 'Run ID:  ' . $State->getId();
                $lines[] = 'Status:  ' . $State->getStatus();
                $lines[] = 'Started: ' . date('Y-m-d H:i:s', $State->getCreatedAt());
                $lines[] = 'PID:     ' . ($pid > 0 ? (string)$pid : 'not available');
                $lines[] = 'Cancel:  ./console update --cancel=' . $State->getId();
            }

            $this->writeErrorBox($lines);
            exit(1);
        }

        $Launcher = QUI\System\Update\RunLauncherFactory::createDefault();
        $Launch = $Launcher->create(null, [
            'arguments' => $this->params ?? []
        ]);

        $this->writeLn('Preparing update ...');
        echo PHP_EOL;

        $exitCode = 0;
        $maxRuns = 5;

        do {
            $exitCode = $this->executeRunProcess($Repository, $Launch);

            if ($exitCode !== 0) {
                exit($exitCode);
            }

            $State = $Repository->load($Launch->getRun()->getState()->getId());
            $maxRuns--;
        } while ($State->getStatus() === QUI\System\Update\RunState::STATUS_RESTART_REQUIRED && $maxRuns > 0);

        if ($State->getStatus() === QUI\System\Update\RunState::STATUS_RESTART_REQUIRED) {
            $this->writeLn('Update run still requires a restart after maximum attempts.', 'red');
            exit(1);
        }
    }

    private function executeRunProcess(
        QUI\System\Update\RunRepository $Repository,
        QUI\System\Update\RunLaunch $Launch
    ): int {
        $command = $Launch->getCliCommand();

        if (!function_exists('proc_open')) {
            system($command, $exitCode);
            return (int)$exitCode;
        }

        $process = proc_open($command, [
            0 => ['file', 'php://stdin', 'r'],
            1 => ['file', 'php://stdout', 'w'],
            2 => ['file', 'php://stderr', 'w']
        ], $pipes);

        if (!is_resource($process)) {
            return 1;
        }

        $status = proc_get_status($process);
        $pid = (int)$status['pid'];

        if ($pid > 0) {
            $State = $Repository->load($Launch->getRun()->getState()->getId());
            $State->setProcess($pid, $command, time());
            $Repository->save($State);
        }

        return proc_close($process);
    }

    private function cancelUpdateRun(string $id): void
    {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');
        $State = $Repository->cancel($id);
        $process = $State->getProcess();
        $pid = is_array($process) ? (int)($process['pid'] ?? 0) : 0;
        $signalSent = false;

        if ($pid > 0 && function_exists('posix_kill')) {
            $isRunning = posix_kill($pid, 0);

            if ($isRunning) {
                $signalSent = posix_kill($pid, 15);
            }
        }

        $lines = [
            'Update run cancelled',
            'The runner state was marked as cancelled.',
            '',
            'Run ID: ' . $State->getId(),
            'Status: ' . $State->getStatus()
        ];

        if ($pid > 0) {
            $lines[] = 'PID:    ' . $pid . ($signalSent ? ' (SIGTERM sent)' : ' (not stopped)');
        } else {
            $lines[] = 'PID:    not available';
        }

        $this->writeErrorBox($lines);
    }

    /**
     * Write a log to the update file
     */
    protected function writeUpdateLog(string $message): void
    {
        QUI\System\Log::write(
            $message,
            QUI\System\Log::LEVEL_NOTICE,
            [
                'params' => [
                    'clearCache' => $this->getArgument('clearCache'),
                    'setDevelopment' => $this->getArgument('setDevelopment'),
                    'check' => $this->getArgument('check'),
                    'set-date' => $this->getArgument('set-date')
                ]
            ],
            'update',
            true
        );
    }

    /**
     * Write buffer to the update log
     */
    public static function writeToLog(string $buffer): void
    {
        if (empty($buffer)) {
            return;
        }

        error_log($buffer, 3, VAR_DIR . 'log/update-' . date('Y-m-d') . '.log');
    }

    public static function onCliOutput(string $message, QUI\Interfaces\System\SystemOutput $Instance): void
    {
        self::writeToLog($message . PHP_EOL);

        if (str_contains($message, '<warning>')) {
            $Instance->writeLn(strip_tags($message), 'cyan');

            // reset color
            if (method_exists($Instance, 'resetColor')) {
                $Instance->resetColor();
            }

            return;
        }

        // update message
        $update = str_contains($message, 'Update: ');
        $updates = str_contains($message, 'Updates: ');
        $upgrade = str_contains($message, ' - Upgrading ');

        $install = str_contains($message, 'Install: ');
        $installs = str_contains($message, 'Installs: ');

        if ($update || $updates || $install || $installs || $upgrade) {
            $message = str_replace(['Updates: ', 'Update: '], '', $message);
            $message = str_replace(['Installs: ', 'Install: '], '', $message);
            $message = str_replace([' - Upgrading '], '', $message);
            $updates = explode(',', $message);

            if ($update || $upgrade) {
                $Instance->writeLn('Updates:', 'yellow');
            } elseif ($install) {
                $Instance->writeLn('Installs:', 'yellow');
            }

            foreach ($updates as $update) {
                $Instance->writeLn('- ' . trim($update), 'purple');
            }

            // reset color
            if (method_exists($Instance, 'resetColor')) {
                $Instance->resetColor();
            }

            return;
        }

        // pull message
        if (str_starts_with($message, '      ')) {
            return;
        }

        // ignoring
        $ignore = [
            'Downloading ',
            '- Downloading ',
            '- Upgrading ',
            '- Syncing ',
            'Cloning to cache ',
            'Executing async command ',
            'Pulling in changes',
            'Reading ',
            'Importing ',
            'Writing ',
            'Executing command ',
            '[304] ',
            'Skipped tag '
        ];


        foreach ($ignore as $ig) {
            $trim = trim($message);

            if (str_starts_with($trim, $ig)) {
                return;
            }
        }

        $Instance->writeLn(strip_tags($message));
    }

    protected function checkFileSystemChanges(): bool
    {
        $Packages = QUI::getPackageManager();
        $Composer = $Packages->getComposer();
        $Composer->unmute();

        $Runner = $Composer->getRunner();
        $result = [];

        $CLIOutput = new QUI\System\Console\Output();
        $CLIOutput->Events->addEvent('onWrite', static function ($message) use (&$result): void {
            $result[] = $message;
            self::writeToLog($message . PHP_EOL);
        });

        $Runner->setOutput($CLIOutput);

        try {
            $Runner->executeComposer('status', [
                '-vvv' => true
            ]);
        } catch (\QUI\Exception $exception) {
            $modified = [];

            foreach ($result as $line) {
                if (
                    str_contains($line, '[400] ')
                    || str_contains($line, '[401] ')
                    || str_contains($line, '[402] ')
                    || str_contains($line, '[403] ')
                    || str_contains($line, '[404] ')
                ) {
                    $this->writeLn();
                    $this->writeLn(
                        '- The update could not check the following package, there was a problem with the package archive.',
                        'yellow'
                    );

                    $this->writeLn('>> ' . $exception->getMessage());
                }

                if (str_contains($line, "    M ")) {
                    $modified[] = $line;
                }
            }

            if (count($modified)) {
                $this->writeLn();
                $this->writeLn('Modified files:', 'light_green');
                $this->writeLn(implode("\n", $modified));
            }


            // fetch changes
            $changes = false;
            $changesList = [];
            $path = '';

            foreach ($result as $line) {
                if (str_contains($line, 'You have changes in the following dependencies:')) {
                    $changes = true;
                    continue;
                }

                if ($changes === false) {
                    continue;
                }

                if (str_contains($line, ':')) {
                    $path = trim($line, ':');
                    $path = str_replace(CMS_DIR, '', $path);

                    $changesList[$path] = [];
                    continue;
                }

                $lines = explode(PHP_EOL, $line);

                foreach ($lines as $l) {
                    if (!empty(trim($l))) {
                        $changesList[$path][] = trim($l);
                    }
                }
            }

            if (count($changesList)) {
                $this->writeLn();
                $this->writeLn('You have changes in the following dependencies:', 'light_green');

                foreach ($changesList as $path => $files) {
                    $this->writeLn($path, 'yellow');
                    $this->resetColor();

                    foreach ($files as $file) {
                        $this->writeLn('- ' . $file);
                    }
                }
            }

            $this->resetColor();

            return true;
        }

        return false;
    }

    protected function executedAnywayQuestion(): bool
    {
        $this->writeLn('Should the update be executed anyway? [y,N]: ', 'red');
        $this->resetColor();
        $answer = $this->readInput();

        if (empty($answer)) {
            return false;
        }

        if (strtolower($answer) === 'y') {
            return true;
        }

        return false;
    }

    private function getComposerVerbosityOptions(): array
    {
        $level = $this->getVerbosityLevel();

        if ($level >= 3) {
            return ['-vvv' => true];
        }

        if ($level === 2) {
            return ['-vv' => true];
        }

        if ($level === 1) {
            return ['-v' => true];
        }

        return [];
    }

    private function getVerbosityLevel(): int
    {
        if ($this->params['-vvv'] ?? false) {
            return 3;
        }

        if ($this->params['-vv'] ?? false) {
            return 2;
        }

        if (
            $this->getArgument('verbose')
            || $this->getArgument('v')
            || ($this->params['--verbose'] ?? false)
            || ($this->params['-v'] ?? false)
        ) {
            return 1;
        }

        return 0;
    }

    /**
     * @param array<int, string> $lines
     */
    private function writeErrorBox(array $lines): void
    {
        $width = 0;

        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }

        $border = str_repeat(' ', $width + 4);
        $redBackground = "\033[41;37m";
        $reset = "\033[0m";

        echo PHP_EOL;
        echo $redBackground . $border . $reset . PHP_EOL;

        foreach ($lines as $line) {
            echo $redBackground . '  ' . str_pad($line, $width) . '  ' . $reset . PHP_EOL;
        }

        echo $redBackground . $border . $reset . PHP_EOL;
        echo PHP_EOL . PHP_EOL;
    }
}
