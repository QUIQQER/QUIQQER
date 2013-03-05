<?php

/**
 * Gruppen unter der Gruppe bekommen
 *
 * @param Integer $id
 * @return Array
 */
function ajax_groups_children($gid)
{
	$Groups   = QUI::getGroups();
	$children = array();

	$Group    = $Groups->get($gid);
	$children = $Group->getChildren();

	return $children;
}
QUI::$Ajax->register('ajax_groups_children', array('gid'), 'Permission::checkSU')

?>