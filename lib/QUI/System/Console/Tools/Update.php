<?php

/**
 *
 * @author hen
 *
 */
namespace QUI\System\Console\Tools;

/**
 *
 * @author hen
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

        $PM->refreshServerList();
        $PM->update();

        $this->write( ' [ok]' );
        $this->writeLn( '' );
    }
}