<?php

/**
 * Search for new packages
 *
 * @param string $str - Search string
 * @param string|integer $from - Sheet start
 * @param string|integer $max - Limit of the results
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_search',
    fn($search) => QUI::getPackageManager()->searchNewPackages($search),
    ['search'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
