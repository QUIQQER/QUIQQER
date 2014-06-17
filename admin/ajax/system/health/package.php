<?php

/**
 * Healthcheck
 *
 * @return String
 */
function ajax_system_health_package($pkg)
{
    return \QUI\System\Checks\Health::packageCheck( $pkg );
}

\QUI::$Ajax->register(
    'ajax_system_health_package',
    array('pkg'),
    'Permission::checkSU'
);
