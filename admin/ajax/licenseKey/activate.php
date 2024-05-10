<?php

/**
 * Activate this QUIQQER system for the currently registered license
 *
 * @return array - Request response
 */

use QUI\System\License;

QUI::$Ajax->registerFunction(
    'ajax_licenseKey_activate',
    static function () {
        try {
            return License::activateSystem();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw $Exception;
        }
    },
    [],
    'Permission::checkAdminUser'
);
