<?php

/**
 * This file contains the autoloader and exception_error_handler and exception_handler
 */

/**
 * Autoloader for the QUIQQER CMS
 */

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
 * @deprecated Use {@see \QUI\Log\ErrorHandler::handleError()} instead
 */
function exception_error_handler(int $errorLevel, string $errorMessage, string $errorFile, int $errorLine): bool
{
    return QUI\Log\ErrorHandler::handleError($errorLevel, $errorMessage, $errorFile, $errorLine);
}

/**
 * Uncaught exception handler
 *
 * @deprecated Use {@see \QUI\Log\ErrorHandler::handleUncaughtException()} instead
 */
function exception_handler(\Throwable $Exception): void
{
    QUI\Log\ErrorHandler::handleUncaughtException($Exception);
}
