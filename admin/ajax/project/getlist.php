<?php

/**
 * Verfügbare Projekte bekommen
 *
 * @return Array
 */
function ajax_project_getlist()
{
	return Projects_Manager::getConfig()->toArray();
}
QUI::$Ajax->register('ajax_project_getlist', false, 'Permission::checkAdminUser');


?>