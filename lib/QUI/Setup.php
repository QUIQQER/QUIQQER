<?php

/**
 * This file contains QUI_Setup
 */

/**
 * QUIQQER Setup
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Setup
{
    /**
     * Excute the QUIQQER Setup
     */
    static function all()
    {
        // not at phpunit
        if ( !isset($_SERVER['argv']) ||
             ( isset( $_SERVER['argv'][0] ) && strpos($_SERVER['argv'][0], 'phpunit') === false) )
        {
            // nur Super User darf dies
            \QUI_Rights_Permission::checkSU();
        }

        // create dirs
        \Utils_System_File::mkdir( BIN_DIR );
        \Utils_System_File::mkdir( LIB_DIR );
        \Utils_System_File::mkdir( USR_DIR );
        \Utils_System_File::mkdir( OPT_DIR );
        \Utils_System_File::mkdir( VAR_DIR );

        // Gruppen erstellen
        \QUI::getGroups()->setup();

        // Rechte setup
        \QUI::getRights()->setup();

        // Benutzer erstellen
        \QUI::getUsers()->setup();

        // Cron Setup
        \System_Cron_Manager::setup();

        // Events Setup
        \QUI_Events_Manager::setup();

        // Desktop Setup
        \QUI_Desktop_Manager::setup();

        // Package Manager
        // QUI_Package_Manager::setup();

        // Upload Manager
        $UploadManager = new \QUI_Upload_Manager();
        $UploadManager->setup();

        // Countries
        \Utils_Countries_Manager::setup();


        /**
         * header dateien
         */
        $str = "<?php require_once '". CMS_DIR ."bootstrap.php'; ?>";

        if ( file_exists( USR_DIR .'header.php' ) ) {
            unlink( USR_DIR .'header.php' );
        }

        if ( file_exists( OPT_DIR .'header.php' ) ) {
            unlink( OPT_DIR .'header.php' );
        }

        file_put_contents( USR_DIR .'header.php', $str );
        file_put_contents( OPT_DIR .'header.php', $str );


        /**
         * Project Setup
         */
        $projects = \Projects_Manager::getProjects( true );

        foreach ( $projects as $Project )
        {
            /* @var $Project Projects_Project */
            $Project->setup();

            // Plugin Setup
            \QUI::getPlugins()->setup( $Project );

            // Media Setup
            $Project->getMedia()->setup();
        }

        /**
         * composer setup
         */
        \QUI::getPackageManager()->refreshServerList();
        \QUI::getPackageManager()->update();

        /**
         * generate translations
         */
        \QUI\Update::importAllLocaleXMLs();
        \QUI\Translator::create();
    }
}
