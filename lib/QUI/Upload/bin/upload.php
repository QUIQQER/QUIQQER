<?php

/**
 * This file contains a php standard upload
 * if the browser supports no html5 upload
 */

$dir = str_replace('quiqqer/quiqqer/lib/QUI/Upload/bin', '', dirname(__FILE__));
define('QUIQQER_SYSTEM', true);

require_once $dir.'header.php';

$QUM = new QUI\Upload\Manager();
QUI::getAjax();

try {
    $QUM->init();

} catch (QUI\Exception $Exception) {
    QUI\System\Log::writeException($Exception);

    $QUM->flushMessage($Exception->toArray());
}
