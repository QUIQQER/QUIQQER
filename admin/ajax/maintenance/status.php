<?php

/**
 * Returns the status of the maintenance status
 *
 * @return Bool
 */

QUI::$Ajax->registerFunction(
    'ajax_maintenance_status',
    static fn() => QUI::conf('globals', 'maintenance'),
    false,
    'Permission::checkAdminUser'
);
