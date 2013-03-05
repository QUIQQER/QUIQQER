<?php

/**
 * Update File installieren
 *
 * @return String
 */
function ajax_system_update_install($File)
{

}

QUI::$Ajax->register(
	'ajax_system_update_install',
    array('File'),
    'Permission::checkSU'
);

?>