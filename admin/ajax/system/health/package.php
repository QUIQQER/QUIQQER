<?php

/**
 * Healthcheck
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_health_package',
    static function ($pkg): array {
        return QUI\System\Checks\Health::packageCheck($pkg);
    },
    ['pkg'],
    'Permission::checkSU'
);
