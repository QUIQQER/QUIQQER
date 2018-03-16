<?php

/**
 * This file contains a php standard upload
 * if the browser supports no html5 upload
 */

$dir = str_replace('quiqqer/quiqqer/lib/QUI/Upload/bin', '', dirname(__FILE__));
define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);

require_once $dir.'header.php';

try {
    $QUM = new QUI\Upload\Manager();
    QUI::getAjax();

    $uploadResult = $QUM->init();

    if (!empty($uploadResult)) {
        $result = [
            'result'      => $uploadResult,
            'maintenance' => QUI::conf('globals', 'maintenance') ? 1 : 0
        ];

        if (QUI::getMessagesHandler()) {
            $result['message_handler'] = QUI::getMessagesHandler()->getMessagesAsArray(
                QUI::getUserBySession()
            );
        }

        // maintenance flag
        echo '<quiqqer>'.json_encode($result).'</quiqqer>';
    }
} catch (QUI\Exception $Exception) {
    QUI\System\Log::writeException($Exception);
    $QUM->flushException($Exception);
} catch (\Exception $Exception) {
    QUI\System\Log::writeException($Exception);
}
