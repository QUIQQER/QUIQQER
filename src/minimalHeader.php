<?php

/**
 * This file contains the main header file
 */

const QUIQQER_MIN_PHP_VERSION = '8.2.0';

header("Content-Type: text/html; charset=utf-8");

// Setting the Cache-Control directive globally at the start of the request is wrong.
// As the header() method immediately sends the header, there is no way to overwrite it later.
// It should be set in the global response object for others to overwrite.
// See also: quiqqer/core#1290
// header("Cache-Control: no-cache, must-revalidate");
// header("Pragma: no-cache");

// date_default_timezone_set( 'Europe/Zurich' );
// Fallback only if no timezone is configured in PHP itself.
$phpIniTimezone = ini_get('date.timezone') ?: '';

if (trim($phpIniTimezone) === '') {
    date_default_timezone_set('UTC');
}

error_reporting(E_ALL);

ini_set('display_errors', false);
ini_set("log_errors", "on");

QUI\Autoloader::checkAutoloader();
QUI::load();
QUI\Utils\System\Debug::marker('header start');

if (QUI::conf('globals', 'display_errors')) {
    ini_set('display_errors', (bool)QUI::conf('', 'display_errors'));
}

if (version_compare(phpversion(), QUIQQER_MIN_PHP_VERSION, '<=')) {
    $message = 'QUIQQER runs with a wrong PHP Version. Please upgrade your PHP Version.';

    QUI\System\Log::addError($message, [
        'version' => phpversion()
    ]);

    exit($message);
}

ini_set("error_log", VAR_DIR . 'log/error' . date('-Y-m-d') . '.log');
ini_set('session.save_path', VAR_DIR . 'sessions');

$errorLevel = E_ALL;
$explicitlyLogDeprecatedErrors = !empty(QUI::conf('globals', 'log_deprecated_errors'));

// disable DEPRECATED warning by default if not in delevopment mode and not explicitly enabled
if (!DEVELOPMENT && $explicitlyLogDeprecatedErrors === false) {
    $errorLevel = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED;
}

error_reporting($errorLevel);

set_error_handler("exception_error_handler", $errorLevel);
set_exception_handler(function (\Throwable $exception): void {
    exception_handler($exception);
});

define('GENERATOR', 'QUIQQER /www.pcsg.de');
//
//define('URL_LIB_DIR', QUI::conf('globals', 'url_lib_dir'));
//define('URL_BIN_DIR', QUI::conf('globals', 'url_bin_dir'));
//define('URL_SYS_DIR', QUI::conf('globals', 'url_sys_dir'));
//
//define('URL_USR_DIR', URL_DIR . str_replace(CMS_DIR, '', USR_DIR));
//define('URL_OPT_DIR', URL_DIR . str_replace(CMS_DIR, '', OPT_DIR));
//define('URL_VAR_DIR', URL_DIR . str_replace(CMS_DIR, '', VAR_DIR));

define('HOST', QUI::conf('globals', 'host'));
define('CACHE', QUI::conf('globals', 'cache'));
define('SALT_LENGTH', QUI::conf('globals', 'saltlength'));
define('MAIL_PROTECT', QUI::conf('globals', 'mailprotection'));
define('ADMIN_CACHE', false);
define('DEBUG_MEMORY', false);

$error_mail = QUI::conf('error', 'mail');

if (!empty($error_mail)) {
    define('ERROR_SEND', $error_mail);
} else {
    define('ERROR_SEND', 0);
}

// GET clearing
foreach ($_GET as $key => $value) {
    $_GET[$key] = QUI\Utils\Security\Orthos::clearFormRequest($value);
}
