<?php

/**
 * PHP Ajax Schnittstelle
 */

require_once 'header.php';

header( "Content-Type: text/plain" );


/**
 * @var Utils_Request_Ajax $ajax
 */

$_rf_files = array();

if ( isset( $_REQUEST['_rf'] ) ) {
    $_rf_files = json_decode( $_REQUEST['_rf'], true );
}


// Plugins <- maybe depricated
if ( isset( $_REQUEST['plugin'] ) )
{
	$file = OPT_DIR . $_REQUEST['plugin'] .'/admin/ajax.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

// ajax package loader
if ( isset( $_REQUEST['package'] ) )
{
    $package = $_REQUEST['package'];
    $dir     = CMS_DIR .'packages/';

    foreach ( $_rf_files as $key => $file )
    {
    	$firstpart = 'package_'. str_replace( '/', '_', $package );
    	$ending    = str_replace( $firstpart, '', $file );

    	$_rf_file = $dir . $package . str_replace( '_', '/', $ending ) .'.php';
    	$_rf_file = Utils_Security_Orthos::clearPath( $_rf_file );

    	if ( file_exists( $_rf_file ) ) {
    		require_once $_rf_file;
    	}
    }
}

foreach ( $_rf_files as $key => $file )
{
    $_rf_file = CMS_DIR .'admin/'. str_replace( '_', '/', $file ) .'.php';

    if ( file_exists( $_rf_file ) ) {
        require_once $_rf_file;
    }
}

/**
 * Ajax Ausgabe
 */
echo QUI::$Ajax->call();










exit;

function ajax_send_support_mail($title, $text, $browser, $url, $mail)
{
	$_mail = new QUI_Mail(array(
		'MAILFromText' => 'Support Mailer'
	));
	$mail_smarty = QUI_Template::getEngine();

	$mail_smarty->assign(array(
		'url'     => $url,
		'browser' => $browser,
		'title'   => $title,
		'text'    => $text,
		'mail'	  => $mail
	));

	$send = array(
		'MailTo'  => 'support@pcsg.de',
		'Subject' => '*** Support Anfrage *** '.$title,
		'IsHTML'  => false
	);

	$template = SYS_DIR .'/template/support_mail_message.html';

	if (!file_exists($template))
	{
		return 'false';
	}

	$send['Body'] = $mail_smarty->fetch( $template );

	try
	{
		$_mail->send($send);
		return 'true';

	} catch (Exception $e)
	{
		return $e->getMessage();
	}
}
QUI::$Ajax->register('ajax_send_support_mail', array('title', 'text', 'browser', 'url', 'mail'));

/**
 * Enter description here...
 *
 */
function ajax_get_robot_txt()
{
	$Users = QUI::getUsers();
	$User  = $Users->getUserBySession();

	if (!$User->isSU()) {
		throw new QException('Nur SuperUser dürfen die robot.txt bearbeiten');
	}

	$f_robot = CMS_DIR .'robots.txt';

	if (!file_exists($f_robot)) {
		return '';
	}

	return file_get_contents($f_robot);
}
QUI::$Ajax->register('ajax_get_robot_txt');

/**
 * Enter description here...
 *
 * @param unknown_type $text
 * @return unknown
 */
function ajax_set_robot_txt($text)
{
	$Users = QUI::getUsers();
	$User  = $Users->getUserBySession();

	if (!$User->isSU()) {
		throw new QException('Nur SuperUser dürfen die robot.txt bearbeiten');
	}

	$f_robot = CMS_DIR .'robots.txt';

	if (file_exists($f_robot)) {
		unlink($f_robot);
	}

	return file_put_contents($f_robot, $text);
}
QUI::$Ajax->register('ajax_set_robot_txt', array('text'));

/**
 * Gibt den Status der Wartungsarbeiten zurück
 */
function ajax_get_maintenance_status()
{
	return QUI::conf('globals','maintenance');
}
QUI::$Ajax->register('ajax_get_maintenance_status');

/**
 * Wartungsarbeiten setzen
 *
 * @param unknown_type $status
 */
function ajax_set_maintenance_status($status)
{
	$Users = QUI::getUsers();
	$User  = $Users->getUserBySession();

	if (!$User->getId()) {
		return;
	}

	if ($User->isAdmin() == false) {
		return;
	}

	$Config = QUI::getConfig(CMS_DIR .'etc/conf.ini');
	$Config->setValue('globals','maintenance', (bool)$status);

	$Config->save();
}
QUI::$Ajax->register('ajax_set_maintenance_status', array('status'));

/**
 * Säubert eine URL
 *
 * @param unknown_type $url
 * @return unknown
 */
function ajax_url_clean($project, $url)
{
	$Project = QUI::getProject($project);

	return Projects_Site_Edit::clearUrl($url, $Project);
}
QUI::$Ajax->register('ajax_url_clean', array('project', 'url'));


$site_functions = array(
	'ajax_site_getsite',
	'ajax_site_getchildren',
	'ajax_site_delete',
	'ajax_site_delete_linked',
	'ajax_site_onload',
	'ajax_site_activate',
	'ajax_site_deactivate',
	'ajax_site_createchild',
	'ajax_site_getTabTpl',
	'ajax_site_setTempFile',
	'ajax_site_delTempFile',
	'ajax_site_saveFromTemp',
	'ajax_site_getTempAttribute',
	'ajax_site_restore',
	'ajax_site_destroy',
	'ajax_site_move',
	'ajax_site_rights_recursive',
	'ajax_site_getsheet',
	'ajax_site_getattribute',
	'ajax_site_get_extra',
	'ajax_site_get_parentids',
	'ajax_site_copy',
	'ajax_site_linked',
	'ajax_site_linked_in',
	'ajax_site_search',
	'ajax_site_super_user_demarcate',
	'ajax_site_search_template',
	'ajax_site_search_window',
	'ajax_site_delete_onlylinked',
	'ajax_site_get_sorts'
);

// Site Funktionen einbauen
if (in_array($_REQUEST['_rf'], $site_functions))
{
	require_once('ajax/ajax.site.php');
	echo $ajax->call();
	exit;
}

$project_functions = array(
	'ajax_project_getproject',
	'ajax_project_gettypes',
	'ajax_project_getsites',
	'ajax_project_getParentIds',
	'ajax_project_clear_trash',
	'ajax_project_create',
	'ajax_project_createbackup',
	'ajax_project_getbackups',
	'ajax_project_deletebackup'
);

if (in_array($_REQUEST['_rf'], $project_functions))
{
	require_once('ajax/ajax.project.php');
	echo $ajax->call();
	exit;
}

/**
 * Ajax für den Media Bereich
 */
$media_functions = array(
	'ajax_media_getImage',     // @depricated
	'ajax_media_getimagesize', // @depricated

	'ajax_media_activate',
	'ajax_media_deactivate',
	'ajax_media_delete',
	'ajax_media_destroy',
	'ajax_media_get_parent_ids',
	'ajax_media_get_data',
	'ajax_media_restore',
	'ajax_media_save_file',

	'ajax_media_trash_getsites',
	'ajax_media_trash_destroy',

	'ajax_media_folder_haschildren',
	'ajax_media_folder_getchildren',
	'ajax_media_folder_createfolder',
	'ajax_media_folder_roundcorners',
    'ajax_media_folder_watermark',
	'ajax_media_search_template',
	'ajax_media_search',
	'ajax_media_get_parents',
	'ajax_media_get_path'
);

if (in_array($_REQUEST['_rf'], $media_functions))
{
	require_once('ajax/ajax.media.php');
	echo $ajax->call();
	exit;
}


$multilingual_functions = array(
	'ajax_multilingual_manager',
	'ajax_multilingual_addlink',
	'ajax_multilingual_removelink',
	'ajax_multilingual_copy'
);

if (in_array($_REQUEST['_rf'], $multilingual_functions))
{
	require_once('ajax/ajax.multilingual.php');
	echo $ajax->call();
	exit;
}

// @todo user und rights aufräumen
$rights_functions = array(
	'ajax_rights_group_get_group',
	'ajax_rights_group_get_children',
	'ajax_rights_group_settings',
	'ajax_rights_group_create_child',
	'ajax_rights_group_template',
	'ajax_rights_group_save',
	'ajax_rights_group_activate',
	'ajax_rights_group_deactivate',
	'ajax_rights_group_getuser',
	'ajax_rights_group_delete',
	'ajax_rights_group_tabs',

	'ajax_rights_users_get_users',
	'ajax_rights_users_get',
	'ajax_rights_users_settings',
	'ajax_rights_users_activate',
	'ajax_rights_users_deactivate',
	'ajax_rights_users_set_settings',
	'ajax_rights_userpopup_template',
	'ajax_rights_user_create_new',
	'ajax_rights_user_del',
	'ajax_rights_users_search'
);

if (in_array($_REQUEST['pcsg_rf'], $rights_functions))
{
	require_once('ajax/ajax.rights.php');
	echo $ajax->call();
	exit;
}


$users_functions = array(
	'ajax_user_setsetting',
	'ajax_user_getsetting',
	'ajax_user_importad_getusers',
	'ajax_user_gettabs',
	'ajax_user_get_tab',
	'ajax_user_get_adress_list',
	'ajax_user_get_adress_add',
	'ajax_user_get_adress_edit',
	'ajax_user_get_adress_delete'
);

if (in_array($_REQUEST['pcsg_rf'], $users_functions))
{
	require_once SYS_DIR .'ajax/ajax.user.php';
	echo $ajax->call();
	exit;
}


$plugins_functions = array(
	'ajax_plugins_getmanager',
	'ajax_plugins_get_update_manager',
	'ajax_plugins_get_remove_manager',
	'ajax_plugins_activate',
	'ajax_plugins_deactivate',
	'ajax_plugins_update',
	'ajax_plugins_install',
	'ajax_plugins_remove',
	'ajax_plugins_setup',
    'ajax_plugins_settings_get_window',
    'ajax_plugins_settings_get_category',
    'ajax_plugins_settings_save',
    'ajax_plugins_settings_get_system_window'
);

if (in_array($_REQUEST['pcsg_rf'], $plugins_functions))
{
	require_once('ajax/ajax.plugins.php');
	echo $ajax->call();
	exit;
}


$trash_functions = array(
	'ajax_trash_getsites',
    'ajax_trash_destroy'
);

if (in_array($_REQUEST['pcsg_rf'], $trash_functions))
{
	require_once 'ajax/ajax.trash.php';
	echo $ajax->call();
	exit;
}


$config_functions = array(
	'ajax_config_global',
	'ajax_config_global_save',
	'ajax_config_projects',
	'ajax_config_projects_save'
);

if (in_array($_REQUEST['pcsg_rf'], $config_functions))
{
	require_once('ajax/ajax.config.php');
	echo $ajax->call();
	exit;
}


$cron_functions = array(
	'ajax_cron_add',
	'ajax_cron_list',
	'ajax_cron_delete',
	'ajax_cron_add_template',
	'ajax_cron_edit_params',
	'ajax_cron_execute'
);

if (in_array($_REQUEST['pcsg_rf'], $cron_functions))
{
	require_once('ajax/ajax.cron.php');
	echo $ajax->call();
	exit;
}


$log_functions = array(
	'ajax_plugins_logs_list',
	'ajax_plugins_logs_get',
	'ajax_plugins_logs_delete',
	'ajax_plugins_logs_send'
);

if (in_array($_REQUEST['pcsg_rf'], $log_functions))
{
	require_once('ajax/ajax.logs.php');
	echo $ajax->call();
	exit;
}



require_once('ajax/ajax.editor.php');


$archive_functions = array(
	'ajax_archive_restore',

);

// Site Funktionen einbauen
if (in_array($_REQUEST['pcsg_rf'], $archive_functions))
{
	require_once('ajax/ajax.archive.php');
	echo $ajax->call();
	exit;
}


// Plugins Globale ajax.php's laden
if (isset($Project) && is_object($Project))
{
	$global_scripts = $Project->getGlobalTypes();

	if (is_array($global_scripts) && isset($global_scripts['ajax']))
	{
		foreach ($global_scripts['ajax'] as $plug => $p)
		{
			// Menüs einlesen
			if (is_array($p))
			{
				foreach ($p as $p_file)
				{
					if (file_exists($p_file)) {
						require_once $p_file;
					}
				}
			}
		}
	}

	// Projekt Ajax File
	$file = USR_DIR .'lib/'. $Project->getAttribute('name') .'/admin/ajax.php';

	if (file_exists($file)) {
		require_once $file;
	}
}

/**
 * Ajax Ausgabe
 */
echo $ajax->call();

exit;

?>