<?php

/**
 * Healthcheck
 *
 * @return String
 */

QUI::getAjax()->registerFunction(
    'ajax_system_health_package',
    static function ($pkg): array {
        return QUI\System\Checks\Health::packageCheck($pkg);
    },
    ['pkg'],
    'Permission::checkSU'
);
