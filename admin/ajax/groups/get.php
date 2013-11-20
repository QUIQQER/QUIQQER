<?php

/**
 * Gruppendaten
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_groups_get($gid)
{
	$Groups = \QUI::getGroups();
	$Group  = $Groups->get((int)$gid);

	$attr = $Group->getAttributes();
	$attr['hasChildren'] = $Group->hasChildren();
	$attr['rights']      = $Group->getRights();

	return $attr;
}
QUI::$Ajax->register('ajax_groups_get', array('gid'), 'Permission::checkSU')

?>