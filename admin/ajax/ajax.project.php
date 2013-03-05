<?php

// Benutzerrechte Prüfung
if (!$User->getId()) {
	exit;
}

if ($User->isAdmin() == false) {
	exit;
}

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @param unknown_type $lang
 * @return unknown
 */
function ajax_project_getproject($name, $lang)
{
	$Project = QUI::getProject($name, $lang);
	return $Project->getAllAttributes();
}
$ajax->register('ajax_project_getproject', array('name', 'lang'));

/**
 * Erstellt ein Project
 *
 * @param unknown_type $name
 * @param unknown_type $lang
 * @param unknown_type $template
 */
function ajax_project_create($newname, $lang, $template)
{
	Projects_Manager::createProject($newname, $lang, $template);
}
$ajax->register('ajax_project_create', array('newname', 'lang', 'template'));

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @param unknown_type $lang
 * @return unknown
 */
function ajax_project_gettypes($name, $lang)
{
	$Project = QUI::getProject($name, $lang);
	return $Project->getTypes();
}
$ajax->register('ajax_project_gettypes', array('name', 'lang'));

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @param unknown_type $lang
 * @return unknown
 */
function ajax_project_createbackup($name, $config, $project, $media, $templates)
{
	$Project = QUI::getProject($name, $lang);

	return $Project->createBackup(
		PT_Bool::JSBool($config),
		PT_Bool::JSBool($project),
		PT_Bool::JSBool($media),
		PT_Bool::JSBool($templates)
	);
}
$ajax->register('ajax_project_createbackup', array('name', 'config', 'project', 'media', 'templates'));

/**
 * Enter description here...
 *
 */
function ajax_project_getbackups($name)
{
	$dir   = VAR_DIR .'backup/'. $name .'/';
	$files = Utils_System_File::readDir($dir);

	$backups = array();

	foreach ($files as $file)
	{
		if (is_dir($dir.$file))
		{
			$backups['b'.(string)$file] = array(
				'date'    => date('d.m.Y H:i:s', $file),
				'folder'  => $file,
				'running' => file_exists(VAR_DIR .'backup/c'.$file) ? 1 : 0,
				'size'    => file_exists($dir.$file.'.zip') ? Utils_System_File::formatSize( filesize($dir.$file.'.zip') ) : 0
			);
		}
	}

	krsort($backups, SORT_STRING);
	return $backups;
}
$ajax->register('ajax_project_getbackups', array('name'));

/**
 * Enter description here...
 *
 * @param unknown_type $name
 */
function ajax_project_deletebackup($archive, $project)
{
	$Users = QUI::getUsers();
	$User  = $Users->getUserBySession();

	if ($User->isSU() == false)
	{
		throw new QException(
			'Sie haben nciht die benötigende Rechte um ein Backup zu löschen'
		);
	}

	if ($archive == '') {
		$archive = 'noarchive';
	}

	$dir = VAR_DIR .'backup/'. $project .'/'. $archive .'/';
	$zip = VAR_DIR .'backup/'. $project .'/'. $archive .'.zip';

	if (file_exists($zip)) {
		unlink($zip);
	}

	Utils_System_File::move($dir, VAR_DIR.'tmp/'.time());

	if (!file_exists($zip) && !is_dir($dir)) {
		return true;
	}

	return false;
}
$ajax->register('ajax_project_deletebackup', array('archive', 'project'));

/**
 * Enter description here...
 *
 * @param String $name
 * @param String $lang
 * @param String $params
 *
 * @return Array
 */
function ajax_project_getsites($name, $lang, $params)
{
	$Project = QUI::getProject($name, $lang);
	$params  = json_decode($params, true);

	if (!is_array($params)) {
		$params = array();
	}

	$result = array();
	$sites  = $Project->getSites($params);

	foreach ($sites as $Site) {
		$result[] = $Site->getAllAttributes();
	}

	return $result;
}
$ajax->register('ajax_project_getsites', array('name', 'lang', 'params'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $id
 * @return unknown
 *
 * @deprecated use ajax_site_get_parentids
 */
function ajax_project_getParentIds($project, $lang, $id)
{
    if (!function_exists('ajax_site_get_parentids')) {
		require_once SYS_DIR .'ajax/ajax.site.php';
	}

	return ajax_site_get_parentids($project, $lang, $id);
}
$ajax->register('ajax_project_getParentIds', array('project', 'lang', 'id'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 */
function ajax_project_clear_trash($project, $lang)
{
	$Project = QUI::getProject($project, $lang);

	$sites = $Project->getSitesIds(array(
		'where' => array(
			'deleted' 	=> 1,
			'active'	=> -1
		)
	));

	foreach ($sites as $site)
	{
        $Site = new Projects_Site_Edit($Project, (int)$site['id']);

	    $Site->deleteTemp();
		$Site->refresh();
		$Site->destroy();
	}
}
$ajax->register('ajax_project_clear_trash', array('project', 'lang'));

?>