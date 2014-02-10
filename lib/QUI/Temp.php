<?php

/**
 * This file contains the \QUI\Temp class
 */

namespace QUI;

/**
 * Temp managed the temp folder
 * It creates temp folders and delete it, provides methods for tempfiles / folders
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class Temp
{
    /**
     * constructor
     *
     * @param String $tempfolder - opath to the tempfolder
     */
    public function __construct($tempfolder)
    {
        $this->_folder = rtrim( $tempfolder, '/' ) .'/';

        if ( !is_dir( $this->_folder ) ) {
            \QUI\Utils\System\File::mkdir( $this->_folder );
        }
    }

    /**
     * Create a temp folder and return the path to it
     *
     * @return String - Path to the folder
     */
    public function createFolder()
    {
        // create a var_dir temp folder
        do
        {
            $folder = $this->_folder . str_replace(
                array(' ', '.'),
                '',
                microtime()
            ) .'/';
        } while ( file_exists( $folder ) );

        \QUI\Utils\System\File::mkdir( $folder );

        return $folder;
    }

    /**
     * Clear the Temp folder
     */
    public function clear()
    {
        if ( system( 'rm -rf '.  $this->_folder ) )
        {
            \QUI\Utils\System\File::mkdir( $this->_folder );
            return;
        }

        // system is not allowed
        \QUI\Utils\System\File::deleteDir( $this->_folder );
        \QUI\Utils\System\File::mkdir( $this->_folder );
    }
}
