<?php

/**
 * Checks if this QUIQQER system has the license to use $package
 *
 * @param string $package
 * @return bool
 */

QUI::$Ajax->registerFunction(
    'ajax_packages_hasLicense',
    static fn($licensePackage) => QUI::getPackageManager()->hasLicense(\QUI\Utils\Security\Orthos::clear($licensePackage)),
    ['licensePackage'],
    'Permission::checkAdminUser'
);
