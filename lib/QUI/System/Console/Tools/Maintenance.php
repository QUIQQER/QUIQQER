<?php

/**
 * \QUI\System\Console\Tools\Health
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Checks the system health
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class Maintenance extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:maintenance')
             ->setDescription('Set the maintenance status. Available commands: --status [on|off]');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn( 'Set maintenance: ' );

        $Config = QUI::getConfig( 'etc/conf.ini.php' );

        if ( $this->getArgument('--status') == 'on' )
        {
            $this->write('on');
            $Config->set( 'globals', 'maintenance', 1 );
        }

        if ( $this->getArgument('--status') == 'off' )
        {
            $this->write('off');
            $Config->set( 'globals', 'maintenance', 0 );
        }

        $Config->save();

        $this->writeLn( '' );
    }
}
