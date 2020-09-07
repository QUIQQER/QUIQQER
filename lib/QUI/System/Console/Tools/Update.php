<?php

/**
 * This file contains QUI\System\Console\Tools\Update
 */

namespace QUI\System\Console\Tools;

use QUI;

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
        $this->writeLn('');
        $this->logBuffer();

        $self     = $this;
        $Packages = QUI::getPackageManager();

        // output events
        $Packages->getComposer()->addEvent('onOutput', function ($Composer, $output, $type) use ($self) {
            $self->write($output);
            $self->writeToLog($output);
        });

        if ($this->getArgument('set-date')) {
            try {
                QUI::getPackageManager()->setLastUpdateDate();
                $this->logBuffer();
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

        // @todo
        if ($this->getArgument('setDevelopment')) {
            $packageList = [];

            $libraries = QUI::getPackageManager()->getInstalled();

            foreach ($libraries as $library) {
                $packageList[$library['name']] = 'dev-dev';
            }

            $packageList['quiqqer/qui']     = 'dev-dev';
            $packageList['quiqqer/quiqqer'] = 'dev-dev';
            $packageList['quiqqer/qui-php'] = 'dev-dev';
            $packageList['quiqqer/utils']   = 'dev-dev';

            foreach ($packageList as $package => $version) {
//                $Packages->setPackage($package, $version);
            }
        }


        if ($this->getArgument('check')) {
            $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'update.log.message.update.via.console'));
            $this->writeLn();
            $this->writeLn();
            $this->logBuffer();

            try {
                $packages = $Packages->getOutdated(true);
            } catch (\Exception $Exception) {
                $this->writeToLog('====== ERROR ======');
                $this->writeToLog($Exception->getMessage());

                return;
            }

            $nameLength    = 0;
            $versionLength = 0;

            // #locale
            if (empty($packages)) {
                $this->writeLn(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.no.updates.available'),
                    'green'
                );

                $this->logBuffer();

                return;
            }

            foreach ($packages as $package) {
                if (\strlen($package['package']) > $nameLength) {
                    $nameLength = \strlen($package['package']);
                }

                if (\strlen($package['oldVersion']) > $versionLength) {
                    $versionLength = \strlen($package['oldVersion']);
                }
            }

            foreach ($packages as $package) {
                $this->write(
                    \str_pad($package['package'], $nameLength + 2, ' '),
                    'green'
                );

                $this->resetColor();
                $this->write(
                    \str_pad($package['oldVersion'], $versionLength + 2, ' ').' -> '
                );

                $this->write($package['version'], 'cyan');
                $this->writeLn();
                $this->logBuffer();
            }

            $this->logBuffer();

            return;
        }

        $Maintenance = new Maintenance();
        $Maintenance->setArgument('status', 'on');
        $Maintenance->execute();

        try {
            $Packages->refreshServerList();
            $Packages->getComposer()->unmute();
            $Packages->update(false, false);

            $this->logBuffer();
            $wasExecuted = QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.execute');
            $webserver   = QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.webserver');

            $this->writeLn($wasExecuted);
            $this->writeToLog($wasExecuted.PHP_EOL);

            $this->writeLn($webserver);
            $this->writeToLog($webserver.PHP_EOL);

            $Httaccess = new Htaccess();
            $Httaccess->execute();

            $Httaccess = new Nginx();
            $Httaccess->execute();

            $this->writeToLog(PHP_EOL);
            $this->writeToLog('✔️'.PHP_EOL);
            $this->writeToLog(PHP_EOL);

            // setup set the last update date
            QUI::getPackageManager()->setLastUpdateDate();
            QUI\Cache\Manager::clearCompleteQuiqqerCache();
            QUI\Cache\Manager::longTimeCacheClearCompleteQuiqqer();
            $this->logBuffer();
        } catch (\Exception $Exception) {
            $this->write(' [error]', 'red');
            $this->writeLn('');
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.error.1').'::'.$Exception->getMessage(),
                'red'
            );

            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'update.message.error'),
                'red'
            );

            $this->writeLn('');

            $this->writeLn(
                'php var/composer/composer.phar --working-dir="'.VAR_DIR.'composer" update',
                'red'
            );

            $this->resetColor();
            $this->writeLn('');
        }

        $this->logBuffer();

        $Maintenance->setArgument('status', 'off');
        $Maintenance->execute();
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
        $buffer = \ob_get_contents();
        $buffer = \trim($buffer);
        $this->writeToLog($buffer);

        @\flush();
        @\ob_flush();
    }

    /**
     * Write buffer to the update log
     *
     * @param string $buffer
     */
    protected function writeToLog($buffer)
    {
        if (empty($buffer)) {
            return;
        }

        \error_log($buffer, 3, VAR_DIR.'log/update-'.\date('Y-m-d').'.log');
    }
}
