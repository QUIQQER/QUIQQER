<?php

/**
 * Check license status
 *
 * @return array
 * @throws QUI\Exception
 */

use QUI\System\License;

QUI::$Ajax->registerFunction(
    'ajax_licenseKey_checkStatus',
    static function () {
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
