<?php

/**
 * This file contains the \QUI\Autoloader
 */

namespace QUI;

use Composer\Autoload\ClassLoader;

use function class_exists;
use function dirname;
use function file_exists;
use function function_exists;
use function interface_exists;
use function is_array;
use function spl_autoload_functions;
use function spl_autoload_unregister;
use function str_replace;
use function substr;

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
     * @var ?ClassLoader
     */
    public static ?ClassLoader $ComposerLoader = null;

    /**
     * Check and unregister composer autoloader.
     *
     * This method checks if any autoload functions are registered and unregisters the composer autoloader.
     * The composer autoloader is identified by an instance of the \Composer\Autoload\ClassLoader class.
     *
     * @return void
     */
    public static function checkAutoloader(): void
    {
        if (static::shouldOtherAutoloadersBeUnregistered()) {
            static::unregisterOtherAutoloaders();
        }

        self::init();
    }

    private static function unregisterOtherAutoloaders(): void
    {
        $autoloaderFunctions = spl_autoload_functions();

        foreach ($autoloaderFunctions as $autoloaderFunction) {
            if (!is_array($autoloaderFunction)) {
                return;
            }

            if (!$autoloaderFunction[0] instanceof ClassLoader) {
                return;
            }

            spl_autoload_unregister($autoloaderFunction);
        }
    }

    /**
     * Initializes the class.
     *
     * Initializes the class by loading the composer autoloader if not already loaded.
     *
     * @return void
     */
    public static function init(): void
    {
        if (self::$ComposerLoader) {
            return;
        }

        self::$ComposerLoader = require dirname(__FILE__, 5) . '/autoload.php';
    }

    /**
     * Load a class, interface, or function dynamically.
     *
     * @param string $classname The name of the class, interface, or function to load.
     *
     * @return bool True if the class, interface, or function is successfully loaded, false otherwise.
     */
    public static function load(string $classname): bool
    {
        self::init();

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
            require_once dirname(__FILE__, 2) . '/classmap/QUI.php';
        }

        if ($classname === 'QUI') {
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
        if (str_starts_with($classname, 'Projects\\')) {
            if (class_exists($classname, false)) {
                return true;
            }

            if (interface_exists($classname, false)) {
                return true;
            }

            $file = USR_DIR . substr($classname, 9) . '.php';
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

        $composerLoaded = (bool)self::$ComposerLoader->loadClass($classname);

        if ($composerLoaded) {
            return true;
        }

        $file = dirname(__FILE__, 2) . '/' . $classname . '.php';
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

        return false;
    }

    public static function shouldOtherAutoloadersBeUnregistered(): bool
    {
        if (getenv('QUIQQER_OTHER_AUTOLOADERS') === 'KEEP') {
            return false;
        }

        return true;
    }
}
