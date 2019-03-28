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
        $this->setName('quiqqer:update')
            ->setDescription('Update the quiqqer system and the quiqqer packages')
            ->addArgument(
                'clearCache',
                'Before execute the Update, clear the complete update cache.',
                false,
                true
            )
            ->addArgument(
                'setDevelopment',
                'Set QUIQQER to the development version',
                false,
                true
            )->addArgument(
                'check',
                'Checks for new updates',
                false,
                true
            )->addArgument(
                'set-date',
                'Updates only the quiqqer update-date',
                false,
                true
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeUpdateLog('====== EXECUTE UPDATE ======');
        $this->writeUpdateLog(QUI::getLocale()->get('quiqqer/quiqqer', 'update.log.message.execute.console'));

        ob_start();

        $this->writeLn('- Starting Update:');
        $this->writeLn('');
        $this->logBuffer();

        $Packages = QUI::getPackageManager();

        if ($this->getArgument('set-date')) {
            QUI::getPackageManager()->setLastUpdateDate();
            $this->logBuffer();

            return;
        }

        if ($this->getArgument('clearCache')) {
            $Packages->clearComposerCache();
        }

        if ($this->getArgument('setDevelopment')) {
            $packageList = [];

            $libraries = QUI::getPackageManager()->getInstalled([
                'type' => 'quiqqer-library'
            ]);

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
            $this->writeLn('Prüfe nach Aktualisierungen...'); // #locale
            $this->writeLn();
            $this->writeLn();
            $this->logBuffer();

            $packages      = $Packages->getOutdated(true);
            $nameLength    = 0;
            $versionLength = 0;

            // #locale
            if (empty($packages)) {
                $this->writeLn(
                    'Ihr System ist aktuell. Es wurden keine Aktualisierungen gefunden',
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

        try {
            $Packages->refreshServerList();
            $Packages->getComposer()->unmute();
            $Packages->update(false, false);

            $this->writeLn('- Update was executed');
            $this->writeLn('- Generating Server files .htaccess and NGINX');
            $this->logBuffer();

            $Httaccess = new Htaccess();
            $Httaccess->execute();

            $Httaccess = new Nginx();
            $Httaccess->execute();

            // setup set the last update date
            QUI::getPackageManager()->setLastUpdateDate();
            QUI\Cache\Manager::clearAll();
        } catch (\Exception $Exception) {
            $this->write(' [error]', 'red');
            $this->writeLn('');
            $this->writeLn(
                'Something went wrong::'.$Exception->getMessage(),
                'red'
            );

            $this->writeLn(
                'If the setup didn\'t worked properly, please test the following command for the update:',
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

        if (!empty($buffer)) {
            QUI\System\Log::write(
                $buffer,
                QUI\System\Log::LEVEL_NOTICE,
                [],
                'update',
                true
            );
        }

        flush();
        ob_flush();
    }
}
