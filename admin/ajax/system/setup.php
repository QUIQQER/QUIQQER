<?php

/**
 * System Setup ausführen
 *
 * @param string $package - optional, Name of a package; no name = full setup
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_system_setup',
    function ($package) {
        if (isset($package) && !empty($package)) {
            QUI::getPackageManager()->setup($package);

            return;
        }

        QUI\Setup::all();
    },
    ['package'],
    'Permission::checkSU'
);
