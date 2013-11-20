<?php

/**
 * This file contains the quiqqer access for the api, cron and console
 */

require 'bootstrap.php';

if ( isset( $_REQUEST['desktop'] ) )
{
    require BIN_DIR .'js/controls/desktop/desktop.php';
    exit;
}

/**
 * Cron execution
 */
if ( isset( $_REQUEST['cron'] ) )
{
    define( 'SYSTEM_INTERN', true );

    ignore_user_abort( true );

    System_Cron_Manager::exec(
        QUI::getUsers()->getSystemUser()
    );

    exit;
}



// no console
if ( php_sapi_name() != 'cli' ) {
    exit;
}

$conf = __DIR__ .'/etc/conf.ini';

if ( !file_exists( $conf ) ) {
    exit(1);
}

// Console aufbauen
$Console = new \System_Console();
$Console->start();
