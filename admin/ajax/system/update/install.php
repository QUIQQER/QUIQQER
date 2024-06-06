<?php

/**
 * Update File installieren
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_install',
    static function ($File): void {
    },
    ['File'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
