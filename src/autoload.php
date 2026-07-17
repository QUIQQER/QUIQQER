<?php

/**
 * This file contains the autoloader and exception_error_handler and exception_handler
 */

/**
 * Autoloader for the QUIQQER CMS
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
 * Despite it's name, this does not acutally handle exceptions - it handles errors.
 * Exceptions are handled by {@see exception_handler()}
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

    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        QUI\System\Log::addDeprecated('Deprecated: ' . $errStr, [
            'file' => $errFile,
            'line' => $errLine
        ]);

        return true;
    }

    $erroreReportingLevel = error_reporting();
    if (!($erroreReportingLevel & $errno)) {
        // This error code is not included in error_reporting, so let it fall through to the standard PHP error handler
        return false;
    }

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

    $exception = new \ErrorException(
        $errorMessage,
        $errno,
        $errno,
        $errFile,
        $errLine
    );

    if ($exit) {
        exception_handler($exception);
        exit(1);
    }

    throw $exception;
}

/**
 * Exception handler
 */
function exception_handler(\Throwable $Exception): void
{
    $exceptionCode = $Exception->getCode();

    $isCacheMissException = $Exception instanceof QUI\Cache\MissException;

    if (!$isCacheMissException) {
        Log::writeException($Exception);
    }

    if (php_sapi_name() === 'cli') {
        echo PHP_EOL;
        echo 'Error: ' . $Exception->getMessage() . PHP_EOL;
        echo 'File: ' . $Exception->getFile() . PHP_EOL;
        echo 'Line:' . $Exception->getLine() . PHP_EOL;

        if ($exceptionCode) {
            echo 'Code: ' . $exceptionCode . PHP_EOL;
        }

        if (!$isCacheMissException) {
            echo 'Details were written to the error log.' . PHP_EOL;
        }

        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }

    echo json_encode([
        'error' => true,
        'message' => 'An error occurred. Check the log for more details.',
        'code' => $exceptionCode
    ]);
}
