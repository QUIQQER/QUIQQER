<?php

/**
 * Web entrypoint for prepared QUIQQER update runs.
 */

declare(strict_types=1);

define('QUIQQER_SYSTEM', true);

$cmsDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;

define('CMS_DIR', $cmsDir);
define('ETC_DIR', $cmsDir . 'etc/');

require $cmsDir . 'bootstrap.php';

$entrypoint = new QUI\System\Update\RunEntrypoint();

exit($entrypoint->execute(
    (string)($_GET['id'] ?? ''),
    VAR_DIR . 'update/runs/',
    QUI\System\Update\DefaultRunActions::create()
));
