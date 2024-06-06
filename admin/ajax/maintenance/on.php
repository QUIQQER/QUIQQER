<?php

/**
 * switches the maintenance on
 */

QUI::$Ajax->registerFunction(
    'ajax_maintenance_on',
    static function (): void {
        $Config = QUI::getConfig('etc/conf.ini.php');
        $Config->setValue('globals', 'maintenance', 1);
        $Config->save();
    },
    false,
    'Permission::checkSU'
);
