<?php

/**
 * Update the system with the local server
 */

QUI::$Ajax->registerFunction(
    'ajax_system_updateWithLocalServer',
    static function (): void {
        QUI::getPackageManager()->updateWithLocalRepository();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
