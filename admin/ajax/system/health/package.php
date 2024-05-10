<?php

/**
 * Healthcheck
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_health_package',
    static fn($pkg): array => QUI\System\Checks\Health::packageCheck($pkg),
    ['pkg'],
    'Permission::checkSU'
);
