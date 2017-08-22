<?php

/**
 * Install / update an uploaded package
 *
 * @param \QUI\QDOM $File - Name of the Package
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_upload_getNotInstalledPackages',
    function () {
        return QUI\Package\LocalServer::getInstance()->getNotInstalledPackage();
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
