<?php

/**
 * switches the maintenance wff
 */
QUI::$Ajax->registerFunction(
    'ajax_maintenance_off',
    function () {
        $Config = QUI::getConfig('etc/conf.ini.php');
        $Config->setValue('globals', 'maintenance', 0);
        $Config->save();
    },
    false,
    'Permission::checkSU'
);
