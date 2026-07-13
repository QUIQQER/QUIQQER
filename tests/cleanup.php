<?php

use QUI\System\TestCleanup;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

define('CMS_DIR', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR);

require CMS_DIR . 'bootstrap.php';

try {
    $projects = TestCleanup::execute();
} catch (Throwable $Exception) {
    fwrite(STDERR, 'Test cleanup failed: ' . $Exception->getMessage() . PHP_EOL);
    exit(1);
}

if ($projects === []) {
    echo 'No PHPUnit projects found.' . PHP_EOL;
    exit(0);
}

echo 'Removed PHPUnit projects: ' . implode(', ', $projects) . PHP_EOL;
