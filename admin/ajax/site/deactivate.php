<?php

/**
 * Seite deaktivieren
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 */
function ajax_site_deactivate($project, $lang, $id)
{
    $Project = QUI::getProject($project, $lang);
	$Site    = new Projects_Site_Edit($Project, (int)$id);

	return $Site->deactivate();
}
QUI::$Ajax->register('ajax_site_deactivate', array('project', 'lang', 'id'), 'Permission::checkAdminUser');

?>