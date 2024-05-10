<?php

/**
 * Update a package or the entire system
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update',
    static function ($package) {
        QUI::getPackageManager()->update($package);
    },
    ['package'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
