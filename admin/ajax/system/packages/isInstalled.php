<?php

/**
 * is the package installed?
 *
 * @param string $package - Name of the package
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_isInstalled',
    fn($packageName) => QUI::getPackageManager()->isInstalled($packageName),
    ['packageName']
);
