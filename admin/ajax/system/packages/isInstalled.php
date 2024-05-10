<?php

/**
 * is the package installed?
 *
 * @param string $package - Name of the package
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_isInstalled',
    static fn($packageName): bool => QUI::getPackageManager()->isInstalled($packageName),
    ['packageName']
);
