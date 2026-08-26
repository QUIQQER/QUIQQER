<?php

/**
 * Update File installieren
 *
 * @return String
 */

QUI::getAjax()->registerFunction(
    'ajax_system_update_install',
    static function ($File): void {
    },
    ['File'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
