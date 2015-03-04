<?php

/**
 * Install / update an uploaded package
 *
 * @param String $File - Name of the Package
 */
function ajax_system_packages_upload_package($File)
{
    \QUI::getPackageManager()->uploadPackage(
        $File->getAttribute( 'filepath' )
    );
}

\QUI::$Ajax->register(
    'ajax_system_packages_upload_package',
    array( 'File' ),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
