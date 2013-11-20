<?php

/**
 * Returns the status of the maintenance status
 *
 * @return Bool
 */

function ajax_maintenance_status()
{
    return \QUI::conf('globals','maintenance');
}
QUI::$Ajax->register('ajax_maintenance_status', false, 'Permission::checkAdminUser');

?>