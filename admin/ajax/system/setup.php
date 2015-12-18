<?php

/**
 * System Setup ausführen
 *
 * @param string $package - optional, Name of a package; no name = full setup
 * @return string
 */
function ajax_system_setup($package)
{
    if (isset($package) && !empty($package)) {
        QUI::getPackageManager()->setup($package);

        return;
    }

    QUI\Setup::all();
}

QUI::$Ajax->register(
    'ajax_system_setup',
    array('package'),
    'Permission::checkSU'
);
