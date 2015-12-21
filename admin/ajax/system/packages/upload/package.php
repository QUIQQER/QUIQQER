<?php

/**
 * Install / update an uploaded package
 *
 * @param \QUI\QDOM $File - Name of the Package
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_upload_package',
    function ($File) {
        /* @var $File \QUI\QDOM */
        QUI::getPackageManager()->uploadPackage(
            $File->getAttribute('filepath')
        );
    },
    array('File'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
