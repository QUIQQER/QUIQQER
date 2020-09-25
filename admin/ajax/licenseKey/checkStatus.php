<?php

use QUI\System\License;

/**
 * Check license status
 *
 * @return array
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_licenseKey_checkStatus',
    function () {
        try {
            return License::getStatus();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw $Exception;
        }
    },
    [],
    'Permission::checkAdminUser'
);
