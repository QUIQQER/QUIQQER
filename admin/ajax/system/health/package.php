<?php

/**
 * Healthcheck
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_health_package',
    fn($pkg) => QUI\System\Checks\Health::packageCheck($pkg),
    ['pkg'],
    'Permission::checkSU'
);
