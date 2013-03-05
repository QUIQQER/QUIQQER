<?php

/**
 * Get Trash sites
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $params
 */
function ajax_trash_sites($project, $lang, $params)
{
	$Project = QUI::getProject($project, $lang);
	$Trash   = $Project->getTrash();

	return $Trash->getList(
	    json_decode($params, true)
	);
}

QUI::$Ajax->register(
	'ajax_trash_sites',
    array('project', 'lang', 'params'),
    'Permission::checkAdminUser'
);


?>