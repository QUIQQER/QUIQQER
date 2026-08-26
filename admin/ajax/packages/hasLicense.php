<?php

/**
 * Checks if this QUIQQER system has the license to use $package
 *
 * @param string $package
 * @return bool
 */

QUI::getAjax()->registerFunction(
    'ajax_packages_hasLicense',
    static function ($licensePackage): bool {
        return QUI::getPackageManager()->hasLicense(\QUI\Utils\Security\Orthos::clear($licensePackage));
    },
    ['licensePackage'],
    'Permission::checkAdminUser'
);
