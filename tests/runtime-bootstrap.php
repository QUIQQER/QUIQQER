<?php

require_once __DIR__ . '/Support/DatabaseEnvironment.php';
require_once __DIR__ . '/Support/LocalTestRuntime.php';
require_once __DIR__ . '/integration/QUI/Projects/ProjectTestHelper.php';

QUITests\Support\LocalTestRuntime::prepare();
if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}
require_once dirname(__DIR__) . '/bootstrap.php';
QUITests\Support\LocalTestRuntime::finishBootstrap();
