<?php

/**
 * System Setup ausführen
 *
 * @param string $package - optional, Name of a package; no name = full setup
 * @return string
 */

QUI::getAjax()->registerFunction(
    'ajax_system_setup',
    static function ($package): void {
        if (!empty($package)) {
            QUI::getPackageManager()->setup($package);

            return;
        }

        QUI\Setup::all();
    },
    ['package'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
