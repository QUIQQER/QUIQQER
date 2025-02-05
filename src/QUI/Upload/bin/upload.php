<?php

/**
 * This file contains a php standard upload
 * if the browser supports no html5 upload
 */

if (!empty($_GET['qs'])) {
    $qs = $_GET['qs'];
    $qs = base64_decode($qs);

    if ($qs) {
        parse_str($qs, $_GET);
        parse_str($qs, $_REQUEST);
    }
}

$dir = str_replace('quiqqer/core/src/QUI/Upload/bin', '', __DIR__);
define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);

require_once $dir . 'header.php';

try {
    QUI\Permissions\Permission::checkPermission('quiqqer.frontend.upload');
} catch (\Exception $Exception) {
    QUI\System\Log::writeDebugException($Exception);
}

$QUM = new QUI\Upload\Manager();
QUI::getAjax();

try {
    $uploadResult = $QUM->init();
} catch (\QUI\Exception $Exception) {
    QUI\System\Log::writeDebugException($Exception);
    $QUM->flushException($Exception);
}

if (empty($uploadResult)) {
    exit;
}

$result = [
    'result' => $uploadResult,
    'maintenance' => QUI::conf('globals', 'maintenance') ? 1 : 0
];

$result['message_handler'] = QUI::getMessagesHandler()->getMessagesAsArray(
    QUI::getUserBySession()
);

// maintenance flag
echo '<quiqqer>' . json_encode($result) . '</quiqqer>';
