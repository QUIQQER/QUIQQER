<?php

/**
 * switches the maintenance wff
 */
function ajax_maintenance_off()
{
    $Config = \QUI::getConfig('etc/conf.ini.php');
    $Config->setValue('globals','maintenance', 0);
    $Config->save();
}

\QUI::$Ajax->register(
    'ajax_maintenance_off',
    false,
    'Permission::checkSU'
);
