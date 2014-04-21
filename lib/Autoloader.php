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

        // Plugins werden gleich übersprungen
           // @todo überdenken, maybe bei plugins auch auf namespaces gehen
        if ( strpos( $classname, 'Plugin_' ) !== false ) {
            return false;
        }

        // Packages
        /*
        if ( strpos( $classname, 'Package\\' ) !== false ) {
            return self::loadPackage( $classname );
        }
        */

        // QUIQQER MVC -> old version, no namespaces
        // remove it if namespaces complet implemented
        $file = LIB_DIR . str_replace( '_', '/', $classname ) .'.php';
        $file = strtolower( dirname( $file ) ) .'/'. basename( $file );

        if ( strpos( $classname, 'QUI_' ) === 0 ) {
            $file = str_replace( '/qui/', '/QUI/', $file );
        }


        if ( file_exists( $file ) )
        {
            require $file;

            if ( class_exists( $classname ) ) {
                return true;
            }

            if ( interface_exists( $classname ) ) {
                return true;
            }
        }

        if ( class_exists( $classname ) ) {
            return true;
        }

        if ( interface_exists( $classname ) ) {
            return true;
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

            //self::$ComposerLoader->register( true );
        }

        return self::$ComposerLoader->loadClass( $classname );
    }

    /**
     * Package Loader
     *
     * @param unknown_type $classname
     * @return Bool
     * @deprecated
     */
    static function loadPackage($classname)
    {
        // load Packages
        $classes = explode('\\', $classname);
        $first   = array_shift($classes);
        $last    = array_pop($classes);

        $file = OPT_DIR . strtolower(implode('/', $classes)) .'/lib/'. ucfirst($last) .'.php';

        if (file_exists($file))
        {
            require $file;

            if (class_exists($classname)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Loads the Stash (Cache Framework)
     *
     * @param String $classname - stash class which is required
     * @return Bool
     * @deprecated
     */
    static function loadStash($classname)
    {
        $file = LIB_DIR .'system/cache/stash/'. str_replace('Stash/', '', strtr($classname, '\\', '/')) .'.php';

        if (file_exists($file))
        {
            require $file;
            return true;
        }

        return false;
    }
}
