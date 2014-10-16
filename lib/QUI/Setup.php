<?php

/**
 * This file contains \QUI\Setup
 */

namespace QUI;

use \QUI\Utils\System\File as SystemFile;

/**
 * QUIQQER Setup
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class Setup
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
            \QUI\Rights\Permission::checkSU();
        }

        \QUI::getSession()->setup();

        // create dirs
        SystemFile::mkdir( BIN_DIR );
        SystemFile::mkdir( LIB_DIR );
        SystemFile::mkdir( USR_DIR );
        SystemFile::mkdir( OPT_DIR );
        SystemFile::mkdir( VAR_DIR );

        // mail queue setup
        \QUI\Mail\Queue::setup();

        // Gruppen erstellen
        \QUI::getGroups()->setup();

        // Rechte setup
        \QUI::getRights()->setup();

        // Benutzer erstellen
        \QUI::getUsers()->setup();

        // Cron Setup
        \QUI::getMessagesHandler()->setup();

        // Events Setup
        \QUI\Events\Manager::setup();

        // workspaces
        \QUI\Workspace\Manager::setup();

        // Upload Manager
        $UploadManager = new \QUI\Upload\Manager();
        $UploadManager->setup();

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
        $projects = \QUI\Projects\Manager::getProjects( true );

        foreach ( $projects as $Project )
        {
            /* @var $Project \QUI\Projects\Project */
            $Project->setup();

            // Plugin Setup
            \QUI::getPlugins()->setup( $Project );

            // Media Setup
            $Project->getMedia()->setup();
        }

        /**
         * composer setup
         */
        $PackageManager = \QUI::getPackageManager();
        $packages       = SystemFile::readDir( OPT_DIR );

        // first we need all databases
        foreach ( $packages as $package )
        {
            if ( $package == 'composer' ) {
                continue;
            }

            if ( $package == 'bin' ) {
                continue;
            }

            if ( !is_dir( OPT_DIR .'/'. $package ) ) {
                continue;
            }

            $package_dir = OPT_DIR .'/'. $package;
            $list        = SystemFile::readDir( $package_dir );

            foreach ( $list as $sub ) {
                $PackageManager->setup( $package .'/'. $sub );
            }
        }


        // import permissions
        \QUI\Update::importAllPermissionsXMLs();

        // generate translations
        \QUI\Update::importAllLocaleXMLs();
        \QUI\Translator::create();

        // generate menu
        \QUI\Update::importAllMenuXMLs();

        // clear cache
        \QUI\Cache\Manager::clearAll();
    }
}
