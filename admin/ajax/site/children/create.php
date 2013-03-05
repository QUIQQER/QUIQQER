<?php

/**
 * Erzeugt ein kind
 *
 * @param String $project	- Project name
 * @param String $lang 		- Project lang
 * @param Integer $id 		- Parent ID
 * @param JSON Array $attributes - child attributes
 */
function ajax_site_children_create($project, $lang, $id, $attributes)
{
	$Project = QUI::getProject($project, $lang);
	$Site    = new Projects_Site_Edit($Project, (int)$id);

	$childid = $Site->createChild(
	    json_decode($attributes, true)
	);

	$Child = new Projects_Site_Edit($Project, $childid);

	return $Child->getAllAttributes();
}
QUI::$Ajax->register('ajax_site_children_create', array('project', 'lang', 'id', 'attributes'));

?>