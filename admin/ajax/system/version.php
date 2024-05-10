<?php

/**
 * Return the current quiqqer version
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_version',
    static fn(): string => QUI::getPackageManager()->getVersion(),
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
