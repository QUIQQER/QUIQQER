<?php

/**
 * This file contains the quiqqer access for the api, cron and console
 */
define('QUIQQER_SYSTEM', true);
require 'bootstrap.php';

// no console
if (php_sapi_name() != 'cli') {
    exit;
}

$conf = ETC_DIR . 'conf.ini.php';

if (!file_exists($conf)) {
    exit(1);
}

// Console aufbauen
define('QUIQQER_CONSOLE', true);

$Console = new \QUI\System\Console();
$Console->start();
