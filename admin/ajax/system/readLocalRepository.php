<?php

/**
 * Read the locale repository and search installable packages
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_readLocalRepository',
    static fn() => QUI::getPackageManager()->readLocalRepository(),
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
