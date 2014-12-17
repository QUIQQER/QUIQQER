<?php

/**
 * This file contains the main header file
 */

header( "Content-Type: text/html; charset=utf-8" );
header( "Cache-Control: public, max-age=". 15 * 60 ); // 15min public caching for proxys

date_default_timezone_set( 'Europe/Zurich' );

error_reporting( E_ALL );
ini_set( 'display_errors', true );

\QUI::load();
\QUI\Utils\System\Debug::marker( 'header start' );

ini_set( 'display_errors', false );
ini_set( "log_errors", "on" );
ini_set( "error_log", VAR_DIR .'log/error'. date('-Y-m-d') .'.log' );

set_error_handler( "exception_error_handler" );

if ( DEVELOPMENT == 1 )
{
    error_reporting( E_ALL );
} else
{
    error_reporting( 0 );
}

define( 'GENERATOR', 'QUIQQER /www.pcsg.de');

define( 'URL_LIB_DIR', URL_DIR . str_replace( CMS_DIR, '', LIB_DIR ) );
define( 'URL_BIN_DIR', URL_DIR . str_replace( CMS_DIR, '', BIN_DIR ) );
define( 'URL_USR_DIR', URL_DIR . str_replace( CMS_DIR, '', USR_DIR ) );
define( 'URL_SYS_DIR', URL_DIR . str_replace( CMS_DIR, '', SYS_DIR ) );
define( 'URL_OPT_DIR', URL_DIR . str_replace( CMS_DIR, '', OPT_DIR ) );
define( 'URL_VAR_DIR', URL_DIR . str_replace( CMS_DIR, '', VAR_DIR ) );

define( 'HOST',         \QUI::conf( 'globals','host' ) );
define( 'CACHE',        \QUI::conf( 'globals','cache' ) );
define( 'SALT_LENGTH',  \QUI::conf( 'globals','saltlength' ) );
define( 'MAIL_PROTECT', \QUI::conf( 'globals','mailprotection' ) );
define( 'ADMIN_CACHE',  false );
define( 'DEBUG_MEMORY', false );

// Cacheflag setzen
\QUI\Cache\Manager::set( 'qui_cache_test', 1 );

try
{
    define( 'CHECK_CACHE', \QUI\Cache\Manager::get( 'qui_cache_test' ) );

} catch ( \QUI\Cache\Exception $e )
{
    define( 'CHECK_CACHE', false );
}

$error_mail = \QUI::conf( 'error','mail' );

if ( !empty( $error_mail ) )
{
    define( 'ERROR_SEND', $error_mail );
} else
{
    define( 'ERROR_SEND', 0 );
}

// Datenbankverbindung aufbauen
try
{
    \QUI::getDataBase();

} catch ( \Exception $Exception )
{
    header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
    header( 'Status: 503 Service Temporarily Unavailable' );

    $Template = \QUI::getTemplateManager()->getEngine();
    $file     = LIB_DIR .'templates/db_error.html';

    if ( \QUI::conf( 'db', 'error_html' ) &&
         file_exists( \QUI::conf( 'db', 'error_html' ) ) )
    {
        $file = \QUI::conf( 'db', 'error_html' );
    }

    try
    {
        echo $Template->fetch( $file );

    } catch ( \QUI\Exception $Exception )
    {
        echo $Template->fetch( LIB_DIR .'templates/db_error.html' );
    }

    \QUI\System\Log::writeException( $Exception );
    exit;
}


// User ist Standard Nobody
$User = \QUI::getUsers()->getNobody();

\QUI::getSession()->start();

if ( isset( $_POST['username'] ) &&
     isset( $_POST['password'] ) &&
     isset( $_POST['login'] ) )
{
    // Falls ein Login versucht wurde
    try
    {
        $User = \QUI::getUsers()->login(
            $_POST['username'],
            $_POST['password']
        );

    } catch ( \QUI\Exception $Exception )
    {
        define( 'LOGIN_FAILED', $Exception->getMessage() );
    }

} elseif ( \QUI::getSession()->get( 'uid' ) )
{
    try
    {
        $User = \QUI::getUserBySession();

    } catch ( \QUI\Exception $Exception )
    {
        define( 'LOGIN_FAILED',  $Exception->getMessage() );
    }
}

// Logout
if ( isset( $_GET['logout'] ) )
{
    $User->logout();
    $User = \QUI::getUsers()->getNobody();

    if ( isset( $_SERVER['REQUEST_URI'] ) &&
         strpos( $_SERVER['REQUEST_URI'], 'logout=1' ) !== false )
    {
        header( 'Location: '. str_replace( 'logout=1', '', $_SERVER['REQUEST_URI'] ) );
        exit;
    }
}

if ( $User->getId() )
{
    // loged in users, no cache
    header( "Cache-Control: no-cache, must-revalidate" ); //HTTP 1.1
    header( "Pragma: no-cache" ); //HTTP 1.0
    header( "Expires: Sat, 26 Jul 1997 05:00:00 GMT" ); // Date in the past
    header( 'Expires: ' . gmdate('D, d M Y H:i:s', time()-60) . ' GMT' );
}

\QUI\Utils\System\Debug::marker( 'header end' );
