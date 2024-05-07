<?php

/**
 * Check for updates
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_check',
    fn() => QUI::getPackageManager()->checkUpdates(),
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
