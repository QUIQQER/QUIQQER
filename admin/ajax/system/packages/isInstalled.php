<?php

/**
 * is the package installed?
 *
 * @param string $package - Name of the package
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_isInstalled',
    function ($packageName) {
        return QUI::getPackageManager()->isInstalled($packageName);
    },
    ['packageName']
);
