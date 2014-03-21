<?php

/**
 * System Setup ausführen
 *
 * @return String
 */
function ajax_system_setup($package)
{
    if ( isset( $package ) && !empty( $package ) )
    {
        \QUI::getPackageManager()->setup( $package );
        return;
    }

    \QUI\Setup::all();
}

\QUI::$Ajax->register(
    'ajax_system_setup',
    array('package'),
    'Permission::checkSU'
);
