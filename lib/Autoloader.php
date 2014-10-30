<?php

/**
 * This file contains the \QUI\Autoloader
 */

namespace QUI;

/**
 * The QUIQQER Autoloader
 * Loads all classes when calling, in dependence of the classes name
 * it includes the composer autoloader
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class Autoloader
{
    /**
     * Composer Autoloader
     * @var \Composer\Autoload\ClassLoader
     */
    static $ComposerLoader = null;

    /**
     * Start the autoload
     *
     * @param String $classname - class which is required
     * @return Bool
     */
    static function load($classname)
    {
        if ( class_exists( $classname ) ) {
            return true;
        }

        if ( interface_exists( $classname ) ) {
            return true;
        }

        // exists quiqqer?
        if ( !class_exists( '\QUI' ) ) {
            require_once __DIR__ .'/QUI.php';
        }

        // if quiqqer not loaded, load it
        if ( !defined( 'CMS_DIR' ) ) {
            \QUI::load();
        }

        if ( $classname == 'QUI' ) {
            return true;
        }

        if ( class_exists( $classname ) ) {
            return true;
        }

        if ( interface_exists( $classname ) ) {
            return true;
        }

        // Projects
        if ( strpos( $classname, 'Projects\\' ) === 0 )
        {
            $file = USR_DIR . substr( $classname, 9 ) .'.php';
            $file = str_replace( '\\', '/', $file );

            if ( file_exists( $file ) ) {
                require $file;
            }

            if ( class_exists( $classname ) ) {
                return true;
            }

            if ( interface_exists( $classname ) ) {
                return true;
            }
        }

        // use now the composer loader
        if ( !self::$ComposerLoader )
        {
            if ( !class_exists( '\Composer\Autoload\ClassLoader' ) ) {
                require OPT_DIR .'composer/ClassLoader.php';
            }

            if ( $classname == 'Composer\Autoload\ClassLoader' ) {
                return true;
            }

            self::$ComposerLoader = new \Composer\Autoload\ClassLoader();

            // include paths
            if ( file_exists( OPT_DIR .'composer/include_paths.php' ) )
            {
                $includePaths = require OPT_DIR .'composer/include_paths.php';

                array_push( $includePaths, get_include_path() );
                set_include_path( join( PATH_SEPARATOR, $includePaths ) );
            }

            // namespaces
            $map      = require OPT_DIR .'composer/autoload_namespaces.php';
            $classMap = require OPT_DIR .'composer/autoload_classmap.php';
            $psr4     = require OPT_DIR .'composer/autoload_psr4.php';

            // add lib to the namespace
            self::$ComposerLoader->add( 'QUI', LIB_DIR );

            foreach ( $map as $namespace => $path ) {
                self::$ComposerLoader->add( $namespace, $path );
            }

            foreach ( $psr4 as $namespace => $path ) {
                self::$ComposerLoader->addPsr4( $namespace, $path );
            }

            if ( $classMap ) {
                self::$ComposerLoader->addClassMap( $classMap );
            }
        }

        return self::$ComposerLoader->loadClass( $classname );
    }
}
