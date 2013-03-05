<?php

/**
 * Get the media trash list
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $params
 */
function ajax_trash_media($project, $params)
{
	$Project = QUI::getProject( $project );
	$Media   = $Project->getMedia();
	$Trash   = $Media->getTrash();

	return $Trash->getList(
	    json_decode( $params, true )
	);
}
QUI::$Ajax->register('ajax_trash_media', array('project', 'params'), 'Permission::checkAdminUser');

?>