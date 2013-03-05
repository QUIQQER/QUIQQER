<?php

/**
 * Daten der Seite bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return Array
 */
function ajax_site_get($project, $lang, $id)
{
	$Project = QUI::getProject($project, $lang);
	$Site    = new Projects_Site_Edit($Project, (int)$id);

	$a = $Site->getAllAttributes();
	$a['has_children'] = $Site->hasChildren();
	$a['config']       = $Site->conf;

	return $a;
}
QUI::$Ajax->register('ajax_site_get', array('project', 'lang', 'id'), 'Permission::checkAdminUser');

?>