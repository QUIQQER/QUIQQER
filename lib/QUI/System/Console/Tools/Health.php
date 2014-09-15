<?php

/**
 * \QUI\System\Console\Tools\Health
 */

namespace QUI\System\Console\Tools;

/**
 * Checks the system health
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class Health extends \QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:health')
             ->setDescription('Checks the system health');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->write( 'Checking system health' );

        try
        {
            \QUI\System\Checks\Health::checkWritable();

            $this->writeLn( 'System Health : OK', 'green' );

        } catch ( \QUI\Exception $Exception )
        {
            $this->writeLn( 'System Health : ERROR', 'red' );
            $this->writeLn( $Exception->getMessage(), 'red' );
        }

        $this->writeLn( '' );
        $this->resetColor();
    }
}
