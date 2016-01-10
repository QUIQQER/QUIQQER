<?php

/**
 * This file contains the autoloader and exception_error_handler and exception_handler
 */

/**
 * Autoloader for the QUIQQER CMS
 *
 * @param string $classname
 *
 * @return boolean
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package quiqqer/quiqqer
 */

require dirname(__FILE__) . '/Autoloader.php';

if (function_exists('spl_autoload_register')) {
    if (function_exists('__autoload')) {
        spl_autoload_register('__autoload');
    }

    spl_autoload_register('__quiqqer_autoload');

} else {
    /**
     * PHP Autoloader
     * Call the QUIQQER Autoloader function
     *
     * @param string $classname - Name of the wanted class
     *
     * @return boolean
     */
    function __autoload($classname)
    {
        return __quiqqer_autoload($classname);
    }
}

/**
 * Main QUIQQER Autoload function
 *
 * @param string $classname - Name of the wanted class
 *
 * @return boolean
 */
function __quiqqer_autoload($classname)
{
    return \QUI\Autoloader::load($classname);
}

/**
 * Error Handler
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @param integer $errno
 * @param string $errstr
 * @param string $errfile
 * @param string $errline
 *
 * @return boolean
 * @throws ErrorException
 */
function exception_error_handler($errno, $errstr, $errfile, $errline)
{
    if ($errstr == 'json_encode(): Invalid UTF-8 sequence in argument') {
        \QUI::getErrorHandler()->setAttribute('show_request', true);
        \QUI::getErrorHandler()
            ->writeErrorToLog($errno, $errstr, $errfile, $errline);
        \QUI::getErrorHandler()->setAttribute('show_request', false);

        return true;
    }

    $l = error_reporting();

    if ($l & $errno) {
        $exit = false;

        switch ($errno) {
            case E_USER_ERROR:
                $type = 'Fatal Error';
                $exit = true;
                break;

            case E_USER_WARNING:
            case E_WARNING:
                $type = 'Warning';
                break;

            case E_USER_NOTICE:
            case E_NOTICE:
            case @E_STRICT:
                $type = 'Notice';
                break;

            case @E_RECOVERABLE_ERROR:
                $type = 'Catchable';
                break;

            default:
                $type = 'Unknown Error';
                $exit = true;
                break;
        }

        $exception = new \ErrorException(
            $type . ': ' . $errstr,
            0,
            $errno,
            $errfile,
            $errline
        );

        if ($exit) {
            exception_handler($exception);
            exit();

        } else {
            throw $exception;
        }
    }

    return false;
}

/**
 * Exception handler
 *
 * @param Exception $Exception
 */
function exception_handler($Exception)
{
    \QUI::getErrorHandler()->writeErrorToLog(
        $Exception->getCode(),
        $Exception->getMessage(),
        $Exception->getFile(),
        $Exception->getLine()
    );

    if (DEVELOPMENT) {
        print(
            $Exception->getMessage() . "\n" . $Exception->getTraceAsString() . "\n"
        );
    }
}
