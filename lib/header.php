<?php

/**
 * This file contains the main header file
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */

const QUIQQER_MIN_PHP_VERSION = '7.4.0';

header("Content-Type: text/html; charset=utf-8");

// Setting the Cache-Control directive globally at the start of the request is wrong.
// As the header() method immediately sends the header, there is no way to overwrite it later.
// It should be set in the global response object for others to overwrite.
// See also: quiqqer/quiqqer#1290
// header("Cache-Control: no-cache, must-revalidate");
// header("Pragma: no-cache");

// date_default_timezone_set( 'Europe/Zurich' );
date_default_timezone_set('UTC');

error_reporting(E_ALL);

ini_set('display_errors', false);
ini_set("log_errors", "on");

QUI\Autoloader::checkAutoloader();
QUI::load();
QUI\Utils\System\Debug::marker('header start');

if (version_compare(phpversion(), QUIQQER_MIN_PHP_VERSION, '<=')) {
    $message = 'QUIQQER runs with a wrong PHP Version. Please upgrade your PHP Version.';

    QUI\System\Log::addError($message, [
        'version' => phpversion()
    ]);

    exit($message);
}

ini_set("error_log", VAR_DIR . 'log/error' . date('-Y-m-d') . '.log');
ini_set('session.save_path', VAR_DIR . 'sessions');

set_error_handler("exception_error_handler");

if (DEVELOPMENT == 1) {
    error_reporting(E_ALL);
} else {
    error_reporting(E_ALL ^ E_NOTICE);
}

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

// Cacheflag setzen
try {
    QUI\Cache\Manager::set('qui_cache_test', 1);
    define('CHECK_CACHE', QUI\Cache\Manager::get('qui_cache_test'));
} catch (QUI\Cache\Exception | Stash\Exception\InvalidArgumentException) {
    define('CHECK_CACHE', false);
}

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


// Datenbankverbindung aufbauen
try {
    QUI::getDataBase();
} catch (Exception $Exception) {
    if (php_sapi_name() === 'cli') {
        echo "\033[1;31m";

        echo 'Database Error: ';
        echo $Exception->getMessage();
        echo "\033[0m";
        echo PHP_EOL;
        exit;
    }

    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');

    $Template = QUI::getTemplateManager()->getEngine();
    $file = LIB_DIR . 'templates/db_error.html';

    if (
        QUI::conf('db', 'error_html')
        && file_exists(QUI::conf('db', 'error_html'))
    ) {
        $file = QUI::conf('db', 'error_html');
    }

    try {
        echo $Template->fetch($file);
    } catch (QUI\Exception $Exception) {
        echo $Template->fetch(LIB_DIR . 'templates/db_error.html');
    }

    QUI\System\Log::writeException($Exception);
    exit;
}


QUI::getSession()->start();

if ((int)QUI::conf('session', 'regenerate')) {
    QUI::getSession()->refresh();
}

$User = QUI::getUserBySession();

// Logout
if (isset($_GET['logout'])) {
    $User->logout();
    $User = QUI::getUsers()->getNobody();

    if (
        isset($_SERVER['REQUEST_URI'])
        && str_contains($_SERVER['REQUEST_URI'], 'logout=1')
    ) {
        header('Location: ' . str_replace('logout=1', '', $_SERVER['REQUEST_URI']));
        exit;
    }
}

$memoryLimit = QUI\Utils\System::getMemoryLimit();
QUI\Utils\System::$memory_limit = $memoryLimit > 0 ? $memoryLimit : false;

QUI::getEvents()->fireEvent('headerLoaded');

QUI\Utils\System\Debug::marker('header end');
