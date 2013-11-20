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
function ajax_site_path($project, $lang, $id)
{
	$Project = \QUI::getProject($project, $lang);
	$Site    = new Projects_Site_Edit($Project, (int)$id);

	$pids    = array();
	$parents = $Site->getParents();

	foreach ($parents as $Parent) {
        $pids[] = $Parent->getId();
	}

	return $pids;
}
QUI::$Ajax->register('ajax_site_path', array('project', 'lang', 'id'), 'Permission::checkAdminUser');

?>