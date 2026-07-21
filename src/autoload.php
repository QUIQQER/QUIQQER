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
 * @author www.pcsg.de (Henning Leutz)
 */
function exception_error_handler(int $errorLevel, string $errorMessage, string $errorFile, int $errorLine): bool
{
    if ($errorMessage === 'json_encode(): Invalid UTF-8 sequence in argument') {
        QUI::getErrorHandler()->setAttribute('show_request', true);
        QUI::getErrorHandler()->writeErrorToLog($errorLevel, $errorMessage, $errorFile, $errorLine);
        QUI::getErrorHandler()->setAttribute('show_request', false);

        return true;
    }

    if (
        str_contains($errorMessage, 'session_regenerate_id()')
        || str_contains($errorMessage, 'session_destroy()')
        || str_contains($errorMessage, 'Required parameter $permissions follows optional parameter $path')
    ) {
        return true;
    }

    $context = [
        'file' => $errorFile,
        'line' => $errorLine
    ];

    $loggingMethod = match ($errorLevel) {
        E_DEPRECATED, E_USER_DEPRECATED => Log::addDeprecated(...),
        E_NOTICE, E_USER_NOTICE, E_STRICT => Log::addNotice(...),
        E_WARNING, E_USER_WARNING => Log::addWarning(...),
        E_RECOVERABLE_ERROR, E_USER_ERROR => Log::addError(...),
        default => Log::addError(...),
    };

    $loggingMethod($errorMessage, $context);

    if ($errorLevel === E_USER_ERROR) {
        if (php_sapi_name() === 'cli') {
            fwrite(
                STDERR,
                'Error: ' . $errorMessage . PHP_EOL
                . 'File: ' . $errorFile . PHP_EOL
                . 'Line: ' . $errorLine . PHP_EOL
            );
        }

        exit(1);
    }

    return true;
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
        $message =
            'Uncaught Exception: ' . $Exception->getMessage() . PHP_EOL
            . 'File: ' . $Exception->getFile() . PHP_EOL
            . 'Line: ' . $Exception->getLine() . PHP_EOL;

        if (!$isCacheMissException) {
            $message .= 'Further details were written to the error log.' . PHP_EOL;
        }

        fwrite(STDERR, $message);
        exit(1);
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
