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
     * Start the autoload
     *
     * @param string $classname - class which is required
     *
     * @return boolean
     */
    public static function load($classname)
    {
        if (class_exists($classname, false)) {
            return true;
        }

        if (interface_exists($classname, false)) {
            return true;
        }

        if (function_exists($classname)) {
            return true;
        }

        // exists quiqqer?
        if (!class_exists('\QUI', false)) {
            require_once __DIR__.'/QUI.php';
        }

        if ($classname == 'QUI') {
            return true;
        }

        if (class_exists($classname, false)) {
            return true;
        }

        if (interface_exists($classname, false)) {
            return true;
        }

        if (function_exists($classname)) {
            return true;
        }

        // Projects
        if (strpos($classname, 'Projects\\') === 0) {
            if (class_exists($classname, false)) {
                return true;
            }

            if (interface_exists($classname, false)) {
                return true;
            }

            $file = USR_DIR.substr($classname, 9).'.php';
            $file = str_replace('\\', '/', $file);

            if (file_exists($file)) {
                require_once $file;
            }

            if (class_exists($classname, false)) {
                return true;
            }

            if (interface_exists($classname, false)) {
                return true;
            }
        }

        // use now the composer loader
        if (!self::$ComposerLoader) {
            self::$ComposerLoader = require dirname(dirname(dirname(dirname(__FILE__)))).'/autoload.php';
        }

        return self::$ComposerLoader->loadClass($classname);
    }
}
