<?php

/**
 * Check for updates
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_update_getOutdated',
    function () {
        return QUI::getPackageManager()->getOutdated();
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
