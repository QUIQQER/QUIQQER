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
     * @param Event $Event
     */
    static function onUpdate(Event $Event)
    {
        $IO       = $Event->getIO();
        $Composer = $Event->getComposer();

        // load quiqqer
        \QUI::load();
        \QUI::getLocale()->setCurrent( 'en' );

        // rights setup, so we have all importend tables
        \QUI_Rights_Manager::setup();

        // WYSIWYG Setup
        \QUI_Editor_Manager::setup();

        // Events setup
        \QUI_Events_Manager::setup();
        \QUI_Events_Manager::clear();


        $packages_dir = $Composer->getConfig()->get( 'vendor-dir' );

        if ( defined( 'OPT_DIR' ) ) {
            $packages_dir = OPT_DIR;
        }

        $packages = \Utils_System_File::readDir( $packages_dir );

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

        // than we need translations
        self::importAllLocaleXMLs( $Composer );


        // compile the translations
        // so the new translations are available
        $IO->write( 'Execute QUIQQER Translator' );

        \QUI\Translator::create();


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

                // permissions
                self::importPermissions(
                    $package_dir .'/'. $sub .'/permissions.xml',
                    $sub,
                    $IO
                );

                // events
                self::importEvents(
                    $package_dir .'/'. $sub .'/events.xml',
                    $sub,
                    $IO
                );
            }
        }

        // permissions
        self::importPermissions(
            CMS_DIR .'/admin/permissions.xml',
            'system',
            $IO
        );


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
     * Import / register quiqqer events
     *
     * @param String $xml_file - path to an engine.xml
     * @param $IO - Composer InputOutput
     */
    static function importEvents($xml_file, $IO=null)
    {
        if ( !file_exists( $xml_file ) ) {
            return;
        }

        \System_Log::write( 'Read: '. $xml_file );

        $events = \Utils_Xml::getEventsFromXml( $xml_file );
        $Events = \QUI::getEvents();

        foreach ( $events as $Event )
        {
            if ( $Event->getAttribute( 'on' ) &&
                 $Event->getAttribute( 'fire' ) )
            {
                $Events->addEvent(
                    $Event->getAttribute( 'on' ),
                    $Event->getAttribute( 'fire' )
                );
            }
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

    /**
     * Reimportation from all menu.xml files
     * Read all packages and import the menu.xml files to the quiqqer system
     *
     * @param Composer $Composer - optional
     */
    static function importAllMenuXMLs($Composer=null)
    {
        $packages_dir = false;

        if ( $Composer ) {
            $packages_dir = $Composer->getConfig()->get( 'vendor-dir' );
        }

        if ( defined( 'OPT_DIR' ) ) {
            $packages_dir = OPT_DIR;
        }

        if ( !$packages_dir )
        {
            throw new \QException(
                'Could not import menu.xml. Package-Dir not found'
            );

            return;
        }

        $packages = \Utils_System_File::readDir( OPT_DIR );

        // then we can read the rest xml files
        foreach ( $packages as $package )
        {
            if ( $package == 'composer' ) {
                continue;
            }

            $package_dir = OPT_DIR .'/'. $package;
            $list        = \Utils_System_File::readDir( $package_dir );

            foreach ( $list as $sub )
            {
                // register menu entries
                self::importMenu(
                    $package_dir .'/'. $sub .'/menu.xml'
                );
            }
        }
    }

    /**
     * Reimportation from all locale.xml files
     *
     * @param Composer $Composer - optional
     */
    static function importAllLocaleXMLs($Composer=null)
    {
        $packages_dir = false;

        if ( $Composer ) {
            $packages_dir = $Composer->getConfig()->get( 'vendor-dir' );
        }

        if ( defined( 'OPT_DIR' ) ) {
            $packages_dir = OPT_DIR;
        }

        if ( !$packages_dir )
        {
            throw new \QException(
                'Could not import menu.xml. Package-Dir not found'
            );

            return;
        }

        $packages = \Utils_System_File::readDir( $packages_dir );

        foreach ( $packages as $package )
        {
            if ( $package == 'composer' ) {
                continue;
            }

            $package_dir = $packages_dir .'/'. $package;
            $list        = \Utils_System_File::readDir( $package_dir );

            foreach ( $list as $sub )
            {
                // locale setup
                self::importLocale(
                    $package_dir .'/'. $sub .'/locale.xml'
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
    }
}

?>