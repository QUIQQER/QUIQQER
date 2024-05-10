<?php

/**
 * Install / update an uploaded package
 *
 * @param \QUI\QDOM $File - Name of the Package
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_upload_package',
    static function ($File) {
        /* @var $File \QUI\QDOM */
        QUI\Package\LocalServer::getInstance()->uploadPackage(
            $File->getAttribute('filepath')
        );
    },
    ['File'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
