<?php

/**
 * \QUI\System\Console\Tools\Health
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Checks the system health
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Health extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('quiqqer:health')->setDescription('Checks the system health');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        $this->write('Checking system health');

        try {
            QUI\System\Checks\Health::checkWritable();

            $this->writeLn('System Health : OK', 'green');
        } catch (QUI\Exception $Exception) {
            $this->writeLn('System Health : ERROR', 'red');
            $this->writeLn($Exception->getMessage(), 'red');
        }

        $this->writeLn();
        $this->resetColor();
    }
}
