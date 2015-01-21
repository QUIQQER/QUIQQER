<?php

/**
 * This file contains QUI\System\Console\Tools\Update
 */
namespace QUI\System\Console\Tools;

/**
 * Update command for the console
 * @author www.pcsg.de (Henning Leutz)
 */
class Update extends \QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:update')
             ->setDescription('Update the quiqqer system and the quiqqer packages');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn( 'Start Update ...' );

        $self = $this;
        $PM   = \QUI::getPackageManager();

        $PM->Events->addEvent('onOutput', function($message) use ($self) {
            $self->writeLn( $message );
        });

        try
        {
            $PM->refreshServerList();
            $PM->update();

            $this->write( ' [ok]' );
            $this->writeLn( '' );

        } catch ( \Exception $Exception )
        {
            $this->write( ' [error]', 'red' );
            $this->writeLn( '' );
            $this->writeLn( 'Something went wrong::'. $Exception->getMessage(), 'red' );
            $this->writeLn( 'If the setup didn\'t worked properly, please test the following command for the update:', 'red' );
            $this->writeLn( '' );

            $this->writeLn(
                'php var/composer/composer.phar --working-dir="'. VAR_DIR .'composer" update', 'red'
            );

            $this->resetColor();
            $this->writeLn( '' );
        }
    }
}