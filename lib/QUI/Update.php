<?php

/**
 * This file contains the \QUI\Update class
 */

namespace QUI;

use Composer\Script\Event;

/**
 * Update from QUIQQER
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 *
 * @todo Backup vor dem Einspielen machen
 */

class Update
{
    /**
     * If a plugin / package would be installed via composer
     *
     * @param Event $Event
     *
     * @todo implement the installation
     */
    static function onInstall(Event $Event)
    {
        // \System_Log::writeRecursive( $event, 'error' );

        $IO = $Event->getIO();

        \QUI::load();

        $IO->write( '\QUI\Update->onInstall' );
        $IO->write( CMS_DIR );
    }

    /**
     * If a plugin / package is updated via composer
     *
     * @param Event $event
     */
    static function onUpdate(Event $Event)
    {
        $IO       = $Event->getIO();
        $Composer = $Event->getComposer();

        // load quiqqer
        \QUI::load();

        // rights setup, so we have all importend tables
        \QUI_Rights_Manager::setup();

        // rights setup, so we have all importend tables
        \QUI_Editor_Manager::setup();

        $packages_dir = $Composer->getConfig()->get( 'vendor-dir' );
        $packages     = \Utils_System_File::readDir( $packages_dir );

        $IO->write('Start QUIQQER updating ...');

        // first we need all databases
        foreach ( $packages as $package )
        {
            if ( $package == 'composer' ) {
                continue;
            }

            $package_dir = $packages_dir .'/'. $package;
            $list        = \Utils_System_File::readDir( $package_dir );

            foreach ( $list as $sub )
            {
                // database setup
                self::importDatabase(
                    $package_dir .'/'. $sub .'/database.xml',
                    $IO
                );
            }
        }

        // then we can read the rest xml files
        foreach ( $packages as $package )
        {
            if ( $package == 'composer' ) {
                continue;
            }

            $package_dir = $packages_dir .'/'. $package;
            $list        = \Utils_System_File::readDir( $package_dir );

            foreach ( $list as $sub )
            {
                // register template engines, if exist in a package
                self::importTemplateEngines(
                    $package_dir .'/'. $sub .'/engines.xml',
                    $IO
                );

                // register wysiwyg editors
                self::importEditors(
                    $package_dir .'/'. $sub .'/wysiwyg.xml',
                    $IO
                );

                // register menu entries
                self::importMenu(
                    $package_dir .'/'. $sub .'/menu.xml',
                    $IO
                );

                // database setup
                self::importLocale(
                    $package_dir .'/'. $sub .'/locale.xml',
                    $IO
                );

                // permissions
                self::importPermissions(
                    $package_dir .'/'. $sub .'/permissions.xml',
                    $sub,
                    $IO
                );
            }
        }


        // system xmls
        $locale_dir = CMS_DIR .'/admin/locale/';
        $locales    = \Utils_System_File::readDir( $locale_dir );

        foreach ( $locales as $locale )
        {
            if ( !is_dir( $locale_dir . $locale ) )
            {
                self::importLocale( $locale_dir . $locale );
                continue;
            }

            $sublocales = \Utils_System_File::readDir( $locale_dir . $locale );

            foreach ( $sublocales as $sublocale ) {
                self::importLocale( $locale_dir . $locale .'/'. $sublocale );
            }
        }

        // permissions
        self::importPermissions(
            CMS_DIR .'/admin/permissions.xml',
            'system',
            $IO
        );


        // compile the translations
        // so the new translations are available
        $IO->write( 'Execute QUIQQER Translator' );

        \QUI\Translator::create();

        $IO->write( 'QUIQQER Update finish' );
    }

    /**
     * Import / register the template engines in an xml file and register it
     *
     * @param String $xml_file - path to an engine.xml
     * @param $IO - Composer InputOutput
     */
    static function importTemplateEngines($xml_file, $IO=null)
    {
        if ( !file_exists( $xml_file ) ) {
            return;
        }

        \System_Log::write( 'Read: '. $xml_file );

        $engines = \Utils_Xml::getTemplateEnginesFromXml( $xml_file );

        foreach ( $engines as $Engine )
        {
            if ( !$Engine->getAttribute( 'class_name' ) ||
                 empty( $Engine->nodeValue ) )
            {
                continue;
            }

            \QUI_Template::registerEngine(
                trim( $Engine->nodeValue ),
                $Engine->getAttribute( 'class_name' )
            );
        }
    }

    /**
     * Import / register the wysiwyg editors
     *
     * @param String $xml_file - path to an engine.xml
     * @param $IO - Composer InputOutput
     */
    static function importEditors($xml_file, $IO=null)
    {
        if ( !file_exists( $xml_file ) ) {
            return;
        }

        \System_Log::write( 'Read: '. $xml_file );

        $editors = \Utils_Xml::getWysiwygEditorsFromXml( $xml_file );

        foreach ( $editors as $Editor )
        {
            if ( !$Editor->getAttribute( 'package' ) ||
                 empty( $Editor->nodeValue ) )
            {
                continue;
            }

            \QUI_Editor_Manager::registerEditor(
                trim( $Editor->nodeValue ),
                $Editor->getAttribute( 'package' )
            );
        }
    }

    /**
     * Import / register the menu items
     * it create a cache file for the package
     *
     * @param String $xml_file - path to an engine.xml
     * @param $IO - Composer InputOutput
     */
    static function importMenu($xml_file, $IO=null)
    {
        if ( !file_exists( $xml_file ) ) {
            return;
        }

        \System_Log::write( 'Read: '. $xml_file );

        $items = \Utils_Xml::getMenuItemsXml( $xml_file );

        if ( !count( $items ) ) {
            return;
        }

        $file = str_replace(
            array(CMS_DIR, '/'),
            array('', '_'),
            $xml_file
        );

        $dir      = VAR_DIR .'cache/menu/';
        $cachfile = $dir . $file;

        \Utils_System_File::mkdir( $dir );

        if ( file_exists( $cachfile ) ) {
            unlink( $cachfile );
        }

        file_put_contents( $cachfile, file_get_contents( $xml_file ) );
    }

    /**
     * Database setup
     * Reads the database.xml and create the definit tables
     *
     * @param String $xml_file - path to an database.xml
     * @param $IO - Composer InputOutput
     */
    static function importDatabase($xml_file, $IO=null)
    {
        if ( !file_exists( $xml_file ) ) {
            return;
        }

        \System_Log::write( 'Read: '. $xml_file );

        \Utils_Xml::importDataBaseFromXml( $xml_file );
    }

	/**
     * Locale setup - translations
     * Reads the locale.xml and import it
     *
     * @param String $xml_file - path to an locale.xml
     * @param $IO - Composer InputOutput
     */
    static function importLocale($xml_file, $IO=null)
    {
        if ( !file_exists( $xml_file ) ) {
            return;
        }

        \System_Log::write( 'Read: '. $xml_file );

        \QUI\Translator::import( $xml_file, false );
    }

	/**
     * Permissions import
     * Reads the permissions.xml and import it
     *
     * @param String $xml_file - path to an locale.xml
     * @param String $src      - Source for the permissions
     * @param $IO - Composer InputOutput
     */
    static function importPermissions($xml_file, $src='', $IO=null)
    {
        if ( !file_exists( $xml_file ) ) {
            return;
        }

        \System_Log::write( 'Read: '. $xml_file );

        \Utils_Xml::importPermissionsFromXml( $xml_file, $src );
    }
}

?>