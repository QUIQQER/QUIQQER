<?php

// no console
if (php_sapi_name() != 'cli') {
    exit;
}

$isComposer = false;

if (!empty($_SERVER['argv'])
    && $_SERVER['argv'][0] === 'quiqqer.php'
    && isset($_SERVER['argv'][1])
    && $_SERVER['argv'][1] === 'composer') {
    $isComposer = true;
}

if (!empty($_SERVER['argv'])
    && $_SERVER['argv'][0] === './console'
    && isset($_SERVER['argv'][1])
    && $_SERVER['argv'][1] === 'composer') {
    $isComposer = true;
}

if ($isComposer) {
    unset($_SERVER['argv'][0]);
    unset($_SERVER['argv'][1]);

    $packagesDir = dirname(__FILE__, 3);
    $cmsDir      = dirname($packagesDir);

    $argv = array_values($_SERVER['argv']);

    $_SERVER['argv'] = array_merge(
        [$packagesDir . '/composer/composer/bin/composer'],
        $argv
    );

    $_SERVER['argv'][] = '--working-dir=' . $cmsDir . '/var/composer';

    require dirname(__FILE__, 3) . '/composer/composer/bin/composer';
    exit;
}


/**
 * This file contains the quiqqer access for the api, cron and console
 */
if (!defined('CMS_DIR')) {
    exit;
}

define('QUIQQER_SYSTEM', true);
require dirname(__FILE__, 3) . '/header.php';

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
