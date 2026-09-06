<?php

use Doctrine\DBAL\DriverManager;
use QUI\Lock\EditingDbalAdapter;
use QUI\Lock\EditingLocks;
use QUI\Lock\Locker;
use QUI\Lock\StoreLogger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Lock\Store\FlockStore;

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);
require dirname(__DIR__, 4) . '/Support/DatabaseEnvironment.php';
if (\QUITests\Support\DatabaseEnvironment::usesCiDatabase()) {
    require dirname(__DIR__, 8) . '/bootstrap.php';
} else {
    require dirname(__DIR__, 4) . '/runtime-bootstrap.php';
}

[$script, $directory, $backend, $id] = $argv;
Locker::setProcessLockStore(new FlockStore($directory . '/mutex'));
if ($backend === 'files') {
    $Store = new FilesystemAdapter('test', 0, $directory . '/records');
} else {
    $Store = new EditingDbalAdapter(DriverManager::getConnection([
        'driver' => 'pdo_sqlite', 'path' => $directory . '/locks.db'
    ]), 'test');
}
$Store->setLogger(new StoreLogger());
$Locks = new EditingLocks($Store);
file_put_contents($directory . '/ready-' . $id, 'ready');
$deadline = microtime(true) + 15;
while (!file_exists($directory . '/go')) {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('Editing lock worker barrier timed out.');
    }
    usleep(10000);
}
$acquired = $Locks->acquire('site:concurrent', 'editor-' . $id, str_repeat(dechex((int)$id + 1), 32));
file_put_contents($directory . '/done-' . $id, $acquired ? '1' : '0');
