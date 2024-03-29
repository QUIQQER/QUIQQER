<?php

/**
 * Update the system with the local server
 */

QUI::$Ajax->registerFunction(
    'ajax_system_updateWithLocalServer',
    function () {
        QUI::getPackageManager()->updateWithLocalRepository();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
