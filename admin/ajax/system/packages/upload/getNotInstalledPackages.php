<?php

/**
 * Install / update an uploaded package
 *
 * @param \QUI\QDOM $File - Name of the Package
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_upload_getNotInstalledPackages',
    fn() => QUI\Package\LocalServer::getInstance()->getNotInstalledPackage(),
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
