<?php

/**
 * Return the current quiqqer version
 *
 * @return String
 */

QUI::getAjax()->registerFunction(
    'ajax_system_version',
    static function (): string {
        return QUI::getPackageManager()->getVersion();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
