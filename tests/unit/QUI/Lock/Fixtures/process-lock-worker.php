<?php

use QUI\Lock\Locker;
use Symfony\Component\Lock\Store\FlockStore;

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);
require dirname(__DIR__, 4) . '/Support/DatabaseEnvironment.php';

if (\QUITests\Support\DatabaseEnvironment::usesCiDatabase()) {
    require dirname(__DIR__, 8) . '/bootstrap.php';
} else {
    require dirname(__DIR__, 4) . '/runtime-bootstrap.php';
}

$directory = $argv[1];
$id = $argv[2];
Locker::setProcessLockStore(new FlockStore($directory));
file_put_contents($directory . '/ready-' . $id, 'ready');
$deadline = microtime(true) + 15;

while (!file_exists($directory . '/go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Process lock worker barrier timed out.');
    }

    usleep(10000);
}

Locker::synchronized('concurrency-test', static function () use ($directory, $id): void {
    // A read-modify-write without its own file lock exposes overlapping callbacks.
    $counter = (int)file_get_contents($directory . '/counter');
    usleep(50000);
    file_put_contents($directory . '/counter', (string)($counter + 1));
    file_put_contents($directory . '/done-' . $id, 'done');
});
