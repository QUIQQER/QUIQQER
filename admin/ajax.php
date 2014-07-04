<?php

/**
 * PHP Ajax Schnittstelle
 */

require_once 'header.php';

header( "Content-Type: text/plain" );

$User = \QUI::getUserBySession();

// Falls Benutzer eingeloggt ist, dann seine Sprache nehmen
if ( $User->getId() && $User->getLang() ) {
    \QUI::getLocale()->setCurrent( $User->getLang() );
}

// language
if ( isset( $_REQUEST['lang'] ) && strlen( $_REQUEST['lang'] ) === 2 ) {
    \QUI::getLocale()->setCurrent( $_REQUEST['lang'] );
}


/**
 * @var \QUI\Utils\Request\Ajax $ajax
 */

$_rf_files = array();

if ( isset( $_REQUEST['_rf'] ) ) {
    $_rf_files = json_decode( $_REQUEST['_rf'], true );
}


// ajax package loader
if ( isset( $_REQUEST['package'] ) )
{
    $package = $_REQUEST['package'];
    $dir     = CMS_DIR .'packages/';

    foreach ( $_rf_files as $key => $file )
    {
        $firstpart = 'package_'. str_replace( '/', '_', $package );
        $ending    = str_replace( $firstpart, '', $file );

        $_rf_file = $dir . $package . str_replace( '_', '/', $ending ) .'.php';
        $_rf_file = \QUI\Utils\Security\Orthos::clearPath( $_rf_file );

        if ( file_exists( $_rf_file ) ) {
            require_once $_rf_file;
        }
    }
}

// admin ajax
foreach ( $_rf_files as $key => $file )
{
    $_rf_file = CMS_DIR .'admin/'. str_replace( '_', '/', $file ) .'.php';

    if ( file_exists( $_rf_file ) ) {
        require_once $_rf_file;
    }
}


/**
 * Ajax Ausgabe
 */
echo \QUI::$Ajax->call();
exit;
