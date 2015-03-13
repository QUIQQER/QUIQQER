<?php

/**
 * \QUI\System\Console\Tools\SystemInfo
 */
namespace QUI\System\Console\Tools;

use QUI;

/**
 * Print the system info - quiqqer version and so on
 * @author www.pcsg.de (Henning Leutz)
 */
class SystemInfo extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:info')
             ->setDescription('Prints system info');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $Package = QUI::getPackageManager()->getInstalledPackage( 'quiqqer/quiqqer' );
        $data    = $Package->getComposerData();

        $this->writeLn( 'QUIQQER Info' );
        $this->writeLn( '' );
        $this->writeLn( '' );

        $print = array_flip( array(
            'name', 'type', 'description',
            'version', 'license', 'authors',
            'support', 'require'
        ) );

        foreach ( $data as $key => $value )
        {
            if ( !isset( $print[ $key ] ) ) {
                continue;
            }

            if ( !is_array( $value ) )
            {
                $this->write( sprintf("%-10s", $key), 'green' );
                $this->resetColor();
                $this->write( "\t\t". $value );
                $this->writeLn( '' );

                continue;
            }

            $this->writeLn( $key, 'green' );
            $this->writeLn( '' );
            $this->resetColor();

            if ( $key == 'authors' )
            {
                foreach ( $value as $arr )
                {
                    foreach ( $arr as $_key => $_value ) {
                        $this->_printArrayEntry( $_key, $_value, "%-15s" );
                    }

                    $this->writeLn( '' );
                }

                continue;
            }

            foreach ( $value as $_key => $_value ) {
                $this->_printArrayEntry( $_key, $_value );
            }
        }

        // server list
        $serverList = QUI::getPackageManager()->getServerList();

        $this->writeLn( 'Server-List', 'green' );
        $this->writeLn( '' );
        $this->resetColor();

        foreach ( $serverList as $server => $data )
        {
            $str = '- '. $server;

            if ( isset( $data['type'] ) ) {
                $str .= ' ('. $data['type'] .')';
            }

            if ( $data['active'] == 1 )
            {
                $this->writeLn( $str );
                $this->resetColor();
            } else
            {
                $this->writeLn( $str, 'red' );
                $this->resetColor();
            }
        }

        $this->writeLn( '' );

        // installed packages
        $this->writeLn( 'Installed packages', 'green' );
        $this->writeLn( '' );
        $this->resetColor();

        $packages = QUI::getPackageManager()->getInstalled();

        foreach ( $packages as $package )
        {
            $str  = '- '. $package[ 'name' ];
            $str .= ' ( '. $package[ 'version' ] .' )';

            $this->writeLn( $str );
        }

        $this->writeLn( '' );
        $this->writeLn( '' );
    }

    /**
     * Print an array entry out
     *
     * @param string $key
     * @param string $value
     * @param string $format - http://php.net/manual/de/function.sprintf.php
     */
    protected function _printArrayEntry($key, $value, $format="%-25s")
    {
        $this->write( sprintf($format, $key), 'purple' );
        $this->resetColor();
        $this->write( "\t\t". $value );
        $this->writeLn( '' );
    }
}
