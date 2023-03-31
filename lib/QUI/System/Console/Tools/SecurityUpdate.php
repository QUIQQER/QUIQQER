<?php

/**
 * This file contains QUI\System\Console\Tools\SecurityUpdate
 */

namespace QUI\System\Console\Tools;

use Composer\Semver\VersionParser;
use Exception;
use QUI;

use function copy;
use function date;
use function error_log;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function flush;
use function json_decode;
use function json_encode;
use function ob_flush;
use function ob_get_contents;
use function str_replace;
use function strpos;
use function trim;
use function unlink;

use const JSON_PRETTY_PRINT;
use const PHP_EOL;
use const VAR_DIR;

/**
 * Update command for the console
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class SecurityUpdate extends QUI\System\Console\Tool
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->systemTool = true;

        $this->setName('quiqqer:security-update')
            ->setDescription('Update the quiqqer system and the quiqqer packages only with security Updates')
            ->addArgument('--mail', 'Which should receive the update log (--mail=info@quiqqer.com)', 'm', true);
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

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'security.update'));
        $this->writeLn('========================');
        $this->writeLn('');
        $this->logBuffer();

        $self         = $this;
        $Packages     = QUI::getPackageManager();
        $dryRun       = true;
        $dryRunOutput = '';

        // output events
        $Packages->getComposer()->addEvent(
            'onOutput',
            function ($Composer, $output, $type) use ($self, &$dryRun, &$dryRunOutput) {
                if ($dryRun) {
                    $dryRunOutput .= $output;
                    return;
                }

                if ($this->getArgument('check')) {
                    return;
                }

                $self->write($output);
                $self->writeToLog($output);
            }
        );

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'security.update.start'));

        $Packages->refreshServerList();

        $Composer = $Packages->getComposer();
        $Composer->unmute();

        // create security composer
        $workingDir = $Composer->getWorkingDir();

        $composerOriginal = $workingDir . 'composer.json';
        $composerBackups  = $workingDir . 'composer-security-update-backup.json';

        try {
            if (!file_exists($composerOriginal)) {
                $this->writeLn('Couldn\'t find the composer.json file.', 'red');
                exit;
            }

            copy($composerOriginal, $composerBackups);

            // get all packages
            $VersionParser = new VersionParser();
            $installed     = QUI::getPackageManager()->getInstalledVersions();
            $packages      = [];

            foreach ($installed as $package => $v) {
                $stability = $VersionParser->parseStability($v);

                if ($stability === 'stable') {
                    $parts = $VersionParser->normalize($v);
                    $parts = explode('.', $parts);
                    $v     = $parts[0] . '.' . $parts[1] . '.*';
                }

                $packages[$package] = $v;
            }

            $composerJSON            = json_decode(file_get_contents($composerOriginal), true);
            $originalRequire         = $composerJSON['require'];
            $composerJSON['require'] = $packages;

            // keep composer.json versions
            // quiqqer/website-locker
            foreach ($originalRequire as $package => $v) {
                if ($package === 'php') {
                    if (!isset($composerJSON['require']['php'])) {
                        $composerJSON['require']['php'] = $v;
                    }
                    continue;
                }

                $stability = $VersionParser->parseStability($v);

                if ($stability !== 'stable') {
                    continue;
                }

                if (strpos($v, '*') !== false) {
                    continue;
                }

                try {
                    $version = $VersionParser->normalize($v);
                } catch (\RuntimeException $e) {
                    continue;
                }

                // wenn version direkt festgesetzt wurde, nicht ändern
                // quiqqer/quiqqer#1192
                if (substr_count($version, '.') === 3) {
                    $composerJSON['require'][$package] = $v;
                }
            }

            file_put_contents($composerOriginal, json_encode($composerJSON, JSON_PRETTY_PRINT));

            // run the test with the security package list
            $Composer->update([
                '--dry-run' => true
            ]);

            $dryRunOutput = explode(PHP_EOL, $dryRunOutput);
            $installs     = 0;
            $updates      = 0;
            $removals     = 0;

            foreach ($dryRunOutput as $line) {
                if (strpos($line, 'Lock file operations:') === false) {
                    continue;
                }

                $line  = str_replace('Lock file operations:', '', $line);
                $lines = explode(',', $line);

                foreach ($lines as $l) {
                    if (strpos($l, 'installs') !== false) {
                        $installs = (int)str_replace('installs', '', $l);
                    } elseif (strpos($l, 'updates') !== false) {
                        $updates = (int)str_replace('updates', '', $l);
                    } elseif (strpos($l, 'removals') !== false) {
                        $removals = (int)str_replace('removals', '', $l);
                    }
                }

                break;
            }

            // run the update with the security package list
            if (!$installs && !$updates && !$removals) {
                $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'security.update.no.updates.found'));
                return;
            }

            $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'security.update.updates.found'));
            $this->writeLn();
            $this->writeLn();
            $dryRun = false;

            // if update exist, activate maintenance
            $Maintenance = new Maintenance();
            $Maintenance->setArgument('status', 'on');
            $Maintenance->execute();


            $Composer->update();

            $this->logBuffer();
            $wasExecuted = QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.execute');
            $webserver   = QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.webserver');

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

            QUI\Setup::all($this);

            // setup set the last update date
            QUI::getPackageManager()->setLastUpdateDate();

            QUI\Cache\Manager::clearCompleteQuiqqerCache();
            QUI\Cache\Manager::longTimeCacheClearCompleteQuiqqer();
            $this->logBuffer();
        } catch (Exception $Exception) {
            $this->write(' [error]', 'red');
            $this->writeLn('');
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.error.1') . '::' . $Exception->getMessage(),
                'red'
            );

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
        } finally {
            // reset the composer jsons
            unlink($composerOriginal);
            copy($composerBackups, $composerOriginal);
            unlink($composerBackups);
        }

        $this->logBuffer();

        // mail
        $mail = $this->getArgument('mail');

        if ($this->getArgument('m')) {
            $mail = $this->getArgument('m');
        }

        if (!empty($mail)) {
            try {
                $logFile = VAR_DIR . 'log/update-' . date('Y-m-d') . '.log';

                $Mailer = QUI::getMailManager()->getMailer();
                $Mailer->addAttachment($logFile);

                $Mailer->setSubject(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'security.update.console.mail.subject', [
                        'host' => HOST
                    ])
                );

                $Mailer->setBody(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'security.update.console.mail.body', [
                        'host' => HOST
                    ])
                );

                $Mailer->send();
            } catch (\PHPMailer\PHPMailer\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
            }
        }

        if (isset($Maintenance)) {
            $Maintenance->setArgument('status', 'off');
            $Maintenance->execute();
        }
    }

    /**
     * Write a log to the update file
     *
     * @param string $message
     */
    protected function writeUpdateLog($message)
    {
        QUI\System\Log::write(
            $message,
            QUI\System\Log::LEVEL_NOTICE,
            [
                'params' => [
                    'clearCache'     => $this->getArgument('clearCache'),
                    'setDevelopment' => $this->getArgument('setDevelopment'),
                    'check'          => $this->getArgument('check'),
                    'set-date'       => $this->getArgument('set-date')
                ]
            ],
            'update',
            true
        );
    }

    /**
     * Log the output buffer to the update log
     */
    protected function logBuffer()
    {
        $buffer = ob_get_contents();
        $buffer = trim($buffer);
        $this->writeToLog($buffer);

        @flush();
        @ob_flush();
    }

    /**
     * Write buffer to the update log
     *
     * @param string $buffer
     */
    protected function writeToLog(string $buffer)
    {
        if (empty($buffer)) {
            return;
        }

        error_log($buffer, 3, VAR_DIR . 'log/update-' . date('Y-m-d') . '.log');
    }
}
