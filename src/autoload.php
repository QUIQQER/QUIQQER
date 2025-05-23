<?php

/**
 * This file contains the autoloader and exception_error_handler and exception_handler
 */

/**
 * Autoloader for the QUIQQER CMS
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */

use QUI\System\Log;

require __DIR__ . '/QUI/Autoloader.php';
require __DIR__ . '/polyfills.php';

/**
 * Main QUIQQER Autoload function
 *
 * @param string $className - Name of the wanted class
 *
 * @return boolean
 */

if (QUI\Autoloader::shouldOtherAutoloadersBeUnregistered()) {
    // unregister other autoload functions (all must run over quiqqer)
    foreach (spl_autoload_functions() as $autoloaderFunction) {
        spl_autoload_unregister($autoloaderFunction);
    }
}

QUI\Autoloader::init();

// @phpstan-ignore-next-line
spl_autoload_register(static function ($className): bool {
    return QUI\Autoloader::load($className);
});

/**
 * Error Handler
 *
 * @throws ErrorException
 * @author www.pcsg.de (Henning Leutz)
 */
function exception_error_handler(int $errno, string $errStr, string $errFile, int $errLine): bool
{
    if ($errStr === 'json_encode(): Invalid UTF-8 sequence in argument') {
        QUI::getErrorHandler()->setAttribute('show_request', true);
        QUI::getErrorHandler()->writeErrorToLog($errno, $errStr, $errFile, $errLine);
        QUI::getErrorHandler()->setAttribute('show_request', false);

        return true;
    }

    if (
        str_contains($errStr, 'session_regenerate_id()')
        || str_contains($errStr, 'session_destroy()')
        || str_contains($errStr, 'Required parameter $permissions follows optional parameter $path')
    ) {
        return true;
    }

    $l = error_reporting();

    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        QUI\System\Log::addInfo('Deprecated: ' . $errStr, [
            'file' => $errFile,
            'line' => $errLine
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

        $errorMessage = $type . ': ' . $errStr;
        $errorMessage .= PHP_EOL . 'File: ' . $errFile;
        $errorMessage .= PHP_EOL . 'Line:' . $errLine;

        $exception = new \ErrorException(
            $errorMessage,
            $errno,
            $errno,
            $errFile,
            $errLine
        );

        if ($exit) {
            exception_handler($exception);
            exit('Unknown Error in QUIQQER exception_error_handler()');
        }

        throw $exception;
    }

    return false;
}

/**
 * Exception handler
 */
function exception_handler(\Throwable $Exception): void
{
    $code = $Exception->getCode();

    if ($code >= 400 && $code < 600) {
        http_response_code($code);
        header('Content-Type: application/json');
    }

    if (php_sapi_name() === 'cli') {
        Log::writeException($Exception);
    }

    Log::addError($Exception->getMessage());

    echo json_encode([
        'error' => true,
        'message' => 'An error occurred. Check the log for more details.',
        'code' => $code
    ]);
}
