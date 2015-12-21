<?php

/**
 * Healthcheck
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_health_package',
    function ($pkg) {
        return QUI\System\Checks\Health::packageCheck($pkg);
    },
    array('pkg'),
    'Permission::checkSU'
);
