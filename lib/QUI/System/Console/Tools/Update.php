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
     * Konstruktor
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
        $this->writeLn('Start Update ...');

        $Packages = QUI::getPackageManager();

        if ($this->getArgument('set-date')) {
            QUI::getPackageManager()->setLastUpdateDate();

            return;
        }

        if ($this->getArgument('clearCache')) {
            $Packages->clearComposerCache();
        }

        if ($this->getArgument('setDevelopment')) {
            $packageList = array();

            $libraries = QUI::getPackageManager()->getInstalled(array(
                'type' => 'quiqqer-library'
            ));

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

            $packages      = $Packages->getOutdated(true);
            $nameLength    = 0;
            $versionLength = 0;

            // #locale
            if (empty($packages)) {
                $this->writeLn(
                    'Ihr System ist aktuell. Es wurden keine Aktualisierungen gefunden',
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
                    str_pad($package['oldVersion'], $versionLength + 2, ' ').' -> '
                );

                $this->write($package['version'], 'cyan');

                $this->writeLn();
            }

            return;
        }

        try {
            $Packages->refreshServerList();
            $Packages->update(false, true);

            $this->write(' [ok]');
            $this->writeLn('');
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
    }
}
