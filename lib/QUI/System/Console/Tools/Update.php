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
use function implode;
use function is_dir;
use function method_exists;
use function preg_replace;
use function str_pad;
use function str_replace;
use function strip_tags;
use function strlen;
use function strpos;
use function strtolower;
use function trim;
use function unlink;

use const CMS_DIR;
use const PHP_EOL;
use const VAR_DIR;

/**
 * Update command for the console
 *
 * @author www.pcsg.de (Henning Leutz)
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
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.update.clearCache'),
                false,
                true
            )
            ->addArgument(
                'setDevelopment',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.update.setDevelopment'),
                false,
                true
            )->addArgument(
                'check',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.update.check'),
                false,
                true
            )->addArgument(
                'set-date',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.update.set-date'),
                false,
                true
            )->addArgument(
                'package',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.update.package.update.check'),
                false,
                true
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeUpdateLog('====== EXECUTE UPDATE ======');
        $this->writeUpdateLog(QUI::getLocale()->get('quiqqer/quiqqer', 'update.log.message.execute.console'));

        Cleanup::clearComposer();

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.start'));

        // check license
        try {
            $licenceData = QUI\System\License::getLicenseData();

            if ($licenceData) {
                $status = QUI\System\License::getStatus();

                if ($status && isset($status['active']) && $status['active'] === false) {
                    $message = QUI::getLocale()->get('quiqqer/quiqqer', 'update.log.message.licenseActivation');
                    $message = preg_replace('#([ ]){2,}#', "$1", $message);
                    $message = str_replace(PHP_EOL . " ", PHP_EOL, $message);
                    $message = trim($message);

                    $this->writeLn();
                    $this->writeLn($message, 'red');
                    $this->writeLn('');
                    $this->writeLn('');
                    $this->resetColor();
                    exit;
                }
            }
        } catch (\Exception $e) {
            $this->writeLn($e->getMessage(), 'red');
            $this->resetColor();
        }

        $Packages = QUI::getPackageManager();

        // output events
        $Packages->getComposer()->addEvent('onOutput', function ($Composer, $output, $type) {
            if ($this->getArgument('check')) {
                return;
            }

            $this->write($output);
            $this->writeToLog($output);
        });

        if ($this->getArgument('set-date')) {
            try {
                QUI::getPackageManager()->setLastUpdateDate();
            } catch (QUI\Exception $Exception) {
                $this->writeToLog('====== ERROR ======');
                $this->writeToLog($Exception->getMessage());
            }

            return;
        }

        if ($this->getArgument('clearCache')) {
            try {
                $Packages->clearComposerCache();
            } catch (QUI\Exception $Exception) {
                $this->writeToLog('====== ERROR ======');
                $this->writeToLog($Exception->getMessage());
            }
        }

        if ($this->getArgument('check')) {
            $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'update.log.message.update.via.console'));
            $this->writeLn();
            $this->writeLn();

            try {
                $packages = $Packages->getOutdated(true);
            } catch (Exception $Exception) {
                $this->writeToLog('====== ERROR ======');
                $this->writeToLog($Exception->getMessage());

                return;
            }

            $nameLength = 0;
            $versionLength = 0;

            // #locale
            if (empty($packages)) {
                $this->writeLn(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.no.updates.available'),
                    'green'
                );

                return;
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
                    str_pad($package['package'], $nameLength + 2, ' '),
                    'green'
                );

                $this->resetColor();
                $this->write(
                    str_pad($package['oldVersion'], $versionLength + 2, ' ') . ' -> '
                );

                $this->write($package['version'], 'cyan');
                $this->writeLn();
            }

            return;
        }

        $Maintenance = new Maintenance();
        $Maintenance->setArgument('status', 'on');
        $Maintenance->execute();


        $this->writeLn('- Filesystem check ...');
        $changes = $this->checkFileSystemChanges();

        if ($changes) {
            $this->writeLn('');
            $this->writeLn('The update has found inconsistencies in the system!', 'yellow');
            $this->resetColor();

            if ($this->executedAnywayQuestion() === false) {
                $Maintenance->setArgument('status', 'off');
                $Maintenance->execute();
                exit;
            }
        }

        // init backup
        $etcBackupFolder = QUI\System\Backup::createEtcBackup();

        // start update routines
        $CLIOutput = new QUI\System\Console\Output();
        $CLIOutput->Events->addEvent('onWrite', function ($message) {
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
                    if (is_dir($localeDir . $entry) && strpos($entry, '_') !== false) {
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

            $wasExecuted = QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.execute');
            $webserver = QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.webserver');

            $this->writeLn($wasExecuted);
            $this->writeToLog($wasExecuted . PHP_EOL);

            $this->writeLn($webserver);
            $this->writeToLog($webserver . PHP_EOL);

            $Htaccess = new Htaccess();
            $Htaccess->execute();

            $NGINX = new Nginx();
            $NGINX->execute();

            $this->writeToLog(PHP_EOL);
            $this->writeToLog('✔️' . PHP_EOL);
            $this->writeToLog(PHP_EOL);

            // setup set the last update date
            QUI::getPackageManager()->setLastUpdateDate();

            QUI\Cache\Manager::clearCompleteQuiqqerCache();
            QUI\Cache\Manager::longTimeCacheClearCompleteQuiqqer();

            // check init backup, with current inits
            $diff = QUI\System\Backup::diff($etcBackupFolder);

            if (!empty($diff)) {
                $this->write($diff);

                $this->writeLn('There have been changes to the ini files!!!', 'red');
                $this->writeLn('Should the etc backup be deleted anyway? [Y,n]', 'red');
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
            $this->writeLn('');
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.error.1') . '::' . $Exception->getMessage(),
                'red'
            );

            if ($Exception instanceof QUI\Exception) {
                QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());
            }

            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.error'),
                'red'
            );

            $this->writeLn('');

            $this->writeLn(
                'php var/composer/composer.phar --working-dir="' . VAR_DIR . 'composer" update',
                'red'
            );

            $this->resetColor();
            $this->writeLn('');
        }

        $Maintenance->setArgument('status', 'off');
        $Maintenance->execute();
    }

    /**
     * Write a log to the update file
     *
     * @param string $message
     */
    protected function writeUpdateLog(string $message)
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
     *
     * @param string $buffer
     */
    public static function writeToLog(string $buffer)
    {
        if (empty($buffer)) {
            return;
        }

        error_log($buffer, 3, VAR_DIR . 'log/update-' . date('Y-m-d') . '.log');
    }

    public static function onCliOutput(string $message, QUI\Interfaces\System\SystemOutput $Instance)
    {
        self::writeToLog($message . PHP_EOL);

        if (strpos($message, '<warning>') !== false) {
            $Instance->writeLn(strip_tags($message), 'cyan');

            // reset color
            if (method_exists($Instance, 'resetColor')) {
                $Instance->resetColor();
            }

            return;
        }

        // update message
        $update = strpos($message, 'Updates: ') !== false;
        $install = strpos($message, 'Installs: ') !== false;

        if ($update || $install) {
            $message = str_replace('Updates: ', '', $message);
            $message = str_replace('Installs: ', '', $message);
            $updates = explode(',', $message);

            if ($update) {
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
        if (strpos($message, '      ') === 0) {
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
        ];


        foreach ($ignore as $ig) {
            $trim = trim($message);

            if (strpos($trim, $ig) === 0) {
                return;
            }
        }

        $Instance->writeLn(strip_tags($message));
    }

    /**
     * @return bool
     */
    protected function checkFileSystemChanges(): bool
    {
        $Packages = QUI::getPackageManager();
        $Composer = $Packages->getComposer();
        $Composer->unmute();

        $Runner = $Composer->getRunner();
        $result = [];

        $CLIOutput = new QUI\System\Console\Output();
        $CLIOutput->Events->addEvent('onWrite', function ($message) use (&$result) {
            $result[] = $message;
            $this->writeToLog($message . PHP_EOL);
        });

        $Runner->setOutput($CLIOutput);

        try {
            $Runner->executeComposer('status', [
                '-vvv' => true
            ]);
        } catch (\QUI\Composer\Exception $Exception) {
            $modified = [];

            foreach ($result as $line) {
                if (strpos($line, '[404] ') !== false) {
                    $path = str_replace('[404] ', '', $line);

                    $this->writeLn();
                    $this->writeLn(
                        '[404] - The update could not check the following package, there was a problem with the package archive.',
                        'red'
                    );

                    $this->writeLn($path);
                }

                if (strpos($line, '[400] ') !== false) {
                    $path = str_replace('[400] ', '', $line);

                    $this->writeLn();
                    $this->writeLn(
                        '[400] - The update could not check the following package, there was a problem with the package archive.',
                        'red'
                    );

                    $this->writeLn($path);
                }

                if (strpos($line, "    M ") !== false) {
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
                if (strpos($line, 'You have changes in the following dependencies:') !== false) {
                    $changes = true;
                    continue;
                }

                if ($changes === false) {
                    continue;
                }

                if (strpos($line, ':') !== false) {
                    $path = trim($line, ':');
                    $path = str_replace(CMS_DIR, '', $path);

                    $changesList[$path] = [];
                    continue;
                }

                $lines = explode(PHP_EOL, $line);

                if (!empty($lines)) {
                    foreach ($lines as $l) {
                        if (!empty(trim($l))) {
                            $changesList[$path][] = trim($l);
                        }
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

    /**
     * @return bool
     */
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
}
