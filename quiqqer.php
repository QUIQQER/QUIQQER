<?php

// no console
if (php_sapi_name() !== 'cli') {
    exit;
}

// read params
$isComposerMode = false;
$isRepairMode = false;
$isPathMigration = false;

$scriptName = $_SERVER['argv'][0] ?? '';
$command = $_SERVER['argv'][1] ?? '';

$validScripts = ['quiqqer.php', './console'];

if (in_array($scriptName, $validScripts, true)) {
    switch ($command) {
        case 'repair':
            $isRepairMode = true;
            break;
        case 'system-migration':
            $isPathMigration = true;
            break;
        case 'composer':
            $isComposerMode = true;
            break;
    }
} else {
    echo 'Error: Please run "./console" only from the main directory of your QUIQQER installation.' . PHP_EOL;
    exit(1);
}

// execute params

if ($isPathMigration) {
    require 'src/pathMigration.php';
    exit;
}

if ($isRepairMode) {
    unset($_SERVER['argv'][0]);
    unset($_SERVER['argv'][1]);

    require 'src/repair.php';
    exit;
}

if ($isComposerMode) {
    unset($_SERVER['argv'][0]);
    unset($_SERVER['argv'][1]);

    $packagesDir = dirname(__FILE__, 3);
    $cmsDir = dirname($packagesDir);

    $argv = array_values($_SERVER['argv']);

    $_SERVER['argv'] = array_merge(
        [$packagesDir . '/composer/composer/bin/composer'],
        $argv
    );

    $_SERVER['argv'][] = '--working-dir=' . $cmsDir . '/var/composer';

    if (file_exists(dirname(__FILE__, 3) . '/composer/composer/bin/composer')) {
        require dirname(__FILE__, 3) . '/composer/composer/bin/composer';
        exit;
    }

    $composerPhar = $cmsDir . '/var/composer/composer.phar';

    if (file_exists($composerPhar)) {
        array_shift($_SERVER['argv']);
        $argString = implode(' ', $_SERVER['argv']);

        system(PHP_BINARY . ' ' . $composerPhar . ' self-update');
        system(PHP_BINARY . ' ' . $composerPhar . ' ' . $argString);
        exit;
    }

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

$conf = ETC_DIR . 'conf.ini.php';

if (!file_exists($conf)) {
    exit(1);
}

// Console aufbauen
define('QUIQQER_CONSOLE', true);


if (!empty($_SERVER['argv']) && $_SERVER['argv'][0] === 'quiqqer.php') {
    /* @deprecated for quiqqer v2.0 */
    echo 'The direct call of quiqqer.php is deprecated.';
    echo PHP_EOL;
    echo 'At the latest in quiqqer v2.0 quiqqer.php will no longer exist.';
    echo PHP_EOL;
    echo 'Please use ./console';
    echo PHP_EOL;
    echo PHP_EOL;
}

$Console = new \QUI\System\Console();
$Console->start();
