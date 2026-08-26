<?php

/**
 * Update the system with the local server
 */

QUI::getAjax()->registerFunction(
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
