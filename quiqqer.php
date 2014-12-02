<?php

/**
 * This file contains the quiqqer access for the api, cron and console
 */

require 'bootstrap.php';

// no console
if ( php_sapi_name() != 'cli' ) {
    exit;
}

$conf = __DIR__ .'/etc/conf.ini.php';

if ( !file_exists( $conf ) ) {
    exit(1);
}

// Console aufbauen
$Console = new \QUI\System\Console();
$Console->start();
