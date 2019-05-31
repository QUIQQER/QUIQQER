<?php

/**
 * This file contains the autoloader and exception_error_handler and exception_handler
 */

/**
 * Autoloader for the QUIQQER CMS
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package quiqqer/quiqqer
 */

require \dirname(__FILE__).'/Autoloader.php';
require \dirname(__FILE__).'/polyfills.php';

/**
 * Main QUIQQER Autoload function
 *
 * @param string $className - Name of the wanted class
 *
 * @return boolean
 */

\spl_autoload_register(function ($className) {
    return \QUI\Autoloader::load($className);
});

/**
 * Error Handler
 *
 * @param integer $errno
 * @param string $errstr
 * @param string $errfile
 * @param string $errline
 *
 * @return boolean
 * @throws ErrorException
 * @author www.pcsg.de (Henning Leutz)
 *
 */
function exception_error_handler($errno, $errstr, $errfile, $errline)
{
    if ($errstr == 'json_encode(): Invalid UTF-8 sequence in argument') {
        QUI::getErrorHandler()->setAttribute('show_request', true);
        QUI::getErrorHandler()->writeErrorToLog($errno, $errstr, $errfile, $errline);
        QUI::getErrorHandler()->setAttribute('show_request', false);

        return true;
    }

    $l = \error_reporting();

    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        QUI\System\Log::addInfo('Deprecated: '.$errstr, [
            'file' => $errfile,
            'line' => $errline
        ]);

        return true;
    }


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
            $type.': '.$errstr,
            $errno,
            $errno,
            $errfile,
            $errline
        );

        if ($exit) {
            \exception_handler($exception);
            exit('Unknown Error in QUIQQER exception_error_handler()');
        }

        throw $exception;
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
    QUI::getErrorHandler()->writeErrorToLog(
        $Exception->getCode(),
        $Exception->getMessage(),
        $Exception->getFile(),
        $Exception->getLine()
    );

    if (DEVELOPMENT) {
        print(
            $Exception->getMessage()."\n".$Exception->getTraceAsString()."\n"
        );
    }
}
