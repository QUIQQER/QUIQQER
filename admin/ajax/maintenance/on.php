<?php

/**
 * switches the maintenance on
 */
function ajax_maintenance_on()
{
    $Config = QUI::getConfig('etc/conf.ini');
    $Config->setValue('globals','maintenance', 1);
    $Config->save();
}
QUI::$Ajax->register('ajax_maintenance_on', false, 'Permission::checkSU');

?>