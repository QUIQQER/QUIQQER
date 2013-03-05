<?php

// Benutzerrechte Prüfung
if (!$User->getId()) {
	exit;
}

if ($User->isAdmin() == false) {
	exit;
}

/**
 * Eine einzelne Seite bekommen
 *
 * @param unknown_type $id
 * @param unknown_type $lang
 * @param unknown_type $project_name
 * @return unknown
 */
function ajax_archive_restore($project, $lang, $id, $date)
{
	$Project = QUI::getProject($project, $lang);
	$Site    = new Projects_Site_Edit($Project, (int)$id);

	return $Site->restoreArchive($date);
}
$ajax->register('ajax_archive_restore', array('project', 'lang', 'id', 'date'));

?>