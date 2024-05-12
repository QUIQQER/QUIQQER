<?php

/**
 * executes the setup of the updated packages
 */

use QUI\Setup;
use QUI\System\Log;
use QUI\System\Output\CliOutput;
use QUI\System\Output\VoidOutput;

define('QUIQQER_SYSTEM', true);
define('SYSTEM_INTERN', true);
define('ETC_DIR', dirname(__FILE__, 8) . '/etc/');

require_once dirname(__FILE__, 4) . '/autoload.php';
require_once dirname(__FILE__, 4) . '/header.php';

QUI\Permissions\Permission::setUser(
    QUI::getUsers()->getSystemUser()
);

$Output = new VoidOutput();

if (php_sapi_name() === 'cli') {
    $Output = new CliOutput();
}

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
