<?php

use QUI\System\License;

/**
 * Activate this QUIQQER system for the currently registered license
 *
 * @return array - Request response
 */
QUI::$Ajax->registerFunction(
    'ajax_licenseKey_activate',
    function () {
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
