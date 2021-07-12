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
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Autoloader
{
    /**
     * Composer Autoloader
     *
     * @var \Composer\Autoload\ClassLoader
     */
    public static $ComposerLoader = null;

    /**
     * init
     */
    public static function init()
    {
        if (self::$ComposerLoader) {
            return;
        }

        self::$ComposerLoader = require \dirname(\dirname(\dirname(\dirname(__FILE__)))).'/autoload.php';

        $dir = \dirname(\dirname(\dirname(\dirname(__FILE__))));

        if (defined('OPT_DIR')) {
            $dir = OPT_DIR;
        }

        self::$ComposerLoader->addClassMap([
            'Stash\\Utilities' => $dir.'/tedivm/stash/src/Stash/Utilities.php'
        ]);
    }
//
//    /**
//     * Check if auto loader is correct
//     */
//    public static function checkAutoloader()
//    {
//        $fs = \spl_autoload_functions();
//
//        foreach ($fs as $f) {
//            // remove composer
//            if (\is_array($f) && $f[0] instanceof \Composer\Autoload\ClassLoader) {
//                \spl_autoload_unregister($f);
//            }
//        }
//
//        self::init();
//    }

    /**
     * Start the autoload
     *
     * @param string $classname - class which is required
     *
     * @return boolean
     */
    public static function load($classname)
    {
        self::init();

        if (\class_exists($classname, false)) {
            return true;
        }

        if (\interface_exists($classname, false)) {
            return true;
        }

        if (\function_exists($classname)) {
            return true;
        }

        // exists quiqqer?
        if (!\class_exists('\QUI', false)) {
            require_once __DIR__.'/QUI.php';
        }

        if ($classname == 'QUI') {
            return true;
        }

        if (\class_exists($classname, false)) {
            return true;
        }

        if (\interface_exists($classname, false)) {
            return true;
        }

        if (\function_exists($classname)) {
            return true;
        }

        // Projects
        if (\strpos($classname, 'Projects\\') === 0) {
            if (\class_exists($classname, false)) {
                return true;
            }

            if (\interface_exists($classname, false)) {
                return true;
            }

            $file = USR_DIR.\substr($classname, 9).'.php';
            $file = \str_replace('\\', '/', $file);

            if (\file_exists($file)) {
                require_once $file;
            }

            if (\class_exists($classname, false)) {
                return true;
            }

            if (\interface_exists($classname, false)) {
                return true;
            }
        }

        return self::$ComposerLoader->loadClass($classname);
    }
}
