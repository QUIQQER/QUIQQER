<?php

/**
 * Activate the local repository
 */
function ajax_system_activateLocalServer()
{
    QUI::getPackageManager()->activateLocalServer();
}

QUI::$Ajax->register(
    'ajax_system_activateLocalServer',
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
