<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/integration/QUI/Projects/ProjectTestHelper.php';
require_once __DIR__ . '/integration/QUI/Projects/ProjectIntegrationTestCase.php';

QUI\System\TestCleanup::register();
