<?php

/**
 * Healthcheck
 *
 * @return String
 */
function ajax_system_health_system()
{
    return \QUI\System\Checks\Health::systemCheck();
}

\QUI::$Ajax->register(
    'ajax_system_health_system',
    false,
    'Permission::checkSU'
);
