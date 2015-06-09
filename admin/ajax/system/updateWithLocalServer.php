<?php

/**
 * Update the system with the local server
 */
function ajax_system_updateWithLocalServer()
{
    QUI::getPackageManager()->updateWithLocalRepository();
}

QUI::$Ajax->register(
    'ajax_system_updateWithLocalServer',
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
