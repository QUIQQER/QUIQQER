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
class Setup extends \QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:setup')
             ->setDescription('Execute the setup from quiqqer');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn( 'Setup executed ...' );

        \QUI\Setup::all();

        $this->write( ' [ok]' );
        $this->writeLn( '' );
    }
}