<?php

/**
 * Returns the status of the maintenance status
 *
 * @return Bool
 */

QUI::getAjax()->registerFunction(
    'ajax_maintenance_status',
    static function () {
        return QUI::conf('globals', 'maintenance');
    },
    false,
    'Permission::checkAdminUser'
);
