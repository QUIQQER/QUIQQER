<?php

/**
 * Healthcheck
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_health_system',
    fn() => QUI\System\Checks\Health::systemCheck(),
    false,
    'Permission::checkSU'
);
