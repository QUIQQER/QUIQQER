<?php

global $Output;
require "header.php";

use QUI\Setup;
use QUI\System\Log;

// quiqqer setup
$Output->writeLn('> Execute QUIQQER setup');

try {
    QUI::getPackage('quiqqer/core')->setup([
        'executePackagesSetup' => false
    ]);
} catch (QUI\Exception $exception) {
    Log::addError($exception->getMessage());
    $Output->writeLn($exception->getMessage(), 'red');
}

// clear cache
$Output->writeLn('> Execute package setups');

try {
    Setup::all($Output);
} catch (Throwable $exception) {
    Log::addError($exception->getMessage());
    $Output->writeLn($exception->getMessage(), 'red');
}
