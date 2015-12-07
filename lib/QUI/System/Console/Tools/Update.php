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

        $self = $this;
        $PM   = QUI::getPackageManager();

        $PM->Events->addEvent('onOutput', function ($message) use ($self) {

            if (strpos($message, '<info>') !== false) {
                $message = str_replace(
                    array('<info>', '</info>'),
                    '',
                    $message
                );

                $self->writeLn($message, 'purple');
                $self->resetColor();
                return;
            }

            if (strpos($message, '<error>') !== false) {
                $message = str_replace(
                    array('<error>', '</error>'),
                    '',
                    $message
                );

                $self->writeLn($message, 'purple');
                $self->resetColor();
                return;
            }

            $self->writeLn($message);
        });

        if ($this->getArgument('--clearCache')) {
            $PM->clearComposerCache();
        }

        if ($this->getArgument('--setDevelopment')) {
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
                QUI::getPackageManager()->setPackage($package, $version);
            }
        }

        try {
            $PM->refreshServerList();
            $PM->update();

            $this->write(' [ok]');
            $this->writeLn('');

        } catch (\Exception $Exception) {
            $this->write(' [error]', 'red');
            $this->writeLn('');
            $this->writeLn(
                'Something went wrong::' . $Exception->getMessage(),
                'red'
            );

            $this->writeLn(
                'If the setup didn\'t worked properly, please test the following command for the update:',
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
    }
}
