<?php

/**
 * Activate the local repository
 */
QUI::$Ajax->registerFunction(
    'ajax_system_activateLocalServer',
    function () {
        QUI::getPackageManager()->activateLocalServer();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
