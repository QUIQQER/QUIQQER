<?php

/**
 * This file contains the QUI_Autoloader
 */

/**
 * The QUIQQER Autoloader
 * Loads all classes when calling, in dependence of the classes name
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Autoloader
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

        // Stash
        if ( strpos( $classname, 'Stash\\' ) !== false ) {
            return self::loadStash( $classname );
        }

        // QUIQQER MVC
        /*
        if ( strpos( $classname, 'QUI\\' ) === 0 ) {
            $classname = str_replace( 'QUI\\', '', $classname );
        }
        */

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
                require CMS_DIR .'packages/composer/ClassLoader.php';
            }

            self::$ComposerLoader = new \Composer\Autoload\ClassLoader();

            $map      = require CMS_DIR .'packages/composer/autoload_namespaces.php';
            $classMap = require CMS_DIR .'packages/composer/autoload_classmap.php';

            foreach ( $map as $namespace => $path ) {
                self::$ComposerLoader->add( $namespace, $path );
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

        $file = CMS_DIR .'packages/'. strtolower(implode('/', $classes)) .'/lib/'. ucfirst($last) .'.php';

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

?>