<?php

/**
 * Read the locale repository and search installable packages
 *
 * @return array
 */
function ajax_system_readLocalRepository()
{
    return QUI::getPackageManager()->readLocalRepository();
}

QUI::$Ajax->register(
    'ajax_system_readLocalRepository',
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
