<?php

/**
 * Check for updates
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_update_check',
    function () {
        return QUI::getPackageManager()->checkUpdates();
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
