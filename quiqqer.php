<?php

/**
 * This file contains the quiqqer access for the api, cron and console
 */
if (!defined('CMS_DIR')) {
    exit;
}

define('QUIQQER_SYSTEM', true);
require CMS_DIR . '/bootstrap.php';

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
