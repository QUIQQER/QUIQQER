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
 * @param unknown_type $project
 * @param unknown_type $id
 * @param unknown_type $name
 * @param unknown_type $title
 * @param unknown_type $alt
 * @param unknown_type $short
 * @return unknown
 */
function ajax_media_save_file($project, $id, $params)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); 	/* @var $Media Media */
	$File    = $Media->get((int)$id); 	/* @var $File MediaFile */

	$params  = json_decode($params, true);

	if (!is_array($params)) {
		return;
	}

	foreach ($params as $field => $value) {
		$File->setAttribute($field, $value);
	}

	// Runde Ecken setzen
	if (isset($params['rc']) &&
		isset($params['rc_bg']) &&
		isset($params['rc_radius']))
	{
		$File->setRoundCorners($params['rc_bg'], $params['rc_radius']);
	} else
	{
		$File->setAttribute('roundcorners', '');
	}

	if (isset($params['wmark']) &&
		isset($params['wpos']))
	{
		$File->setWatermark(array(
		    'image'    => $params['wmark'],
		    'position' => $params['wpos'],
			'active'   => (bool)$params['wactive']
		));
	} else
	{
		$File->setAttribute('watermark', '');
	}

	return $File->save();
}
$ajax->register('ajax_media_save_file', array('project', 'id', 'params'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $id
 * @return unknown
 */
function ajax_media_getimagesize($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia();
	$Obj     = $Media->get( (int)$id );

	if ($Obj->getType() == 'IMAGE')
	{
		return array(
			'width'  => $Obj->getAttribute('image_width'),
			'height' => $Obj->getAttribute('image_height')
		);
	}

	return array(
		'width'  => 0,
		'height' => 0
	);
}
$ajax->register('ajax_media_getimagesize', array('project', 'id'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $id
 * @return unknown
 */
function ajax_media_get_data($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* @var $Media Media */
	$Obj     = $Media->get( (int)$id );

	return $Obj->toArray();
}
$ajax->register('ajax_media_get_data', array('project', 'id'));

/**
 * Erstellt einen neuen Ordner
 *
 * @param String $project
 * @param String $id
 * @param String $name - neuer Name
 * @return Bool
 */
function ajax_media_folder_createfolder($project, $id, $foldername)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */
	$Folder  = $Media->get((int)$id);

	if ($Folder->getType() != 'FOLDER') {
		return false;
	}

	$Folder->createFolder($foldername);
}
$ajax->register('ajax_media_folder_createfolder', array('project', 'id', 'foldername'));

/**
 * Erstellt für alle Bilder Runde Ecken
 *
 * @param unknown_type $project
 * @param unknown_type $id
 * @param unknown_type $background
 * @param unknown_type $radius
 * @throws QException
 */
function ajax_media_folder_roundcorners($project, $id, $background, $radius)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */
	$Folder  = $Media->get( (int)$id );

	if ($Folder->getType() != 'FOLDER') {
		throw new QException('Diese Funktion steht nur bei Ordnern zur Verfügung');
	}

	return $Folder->setRoundCorners($background, $radius);
}
$ajax->register('ajax_media_folder_roundcorners', array('project', 'id', 'background', 'radius'));

/**
 * Setzt Wasserzeichen rekursiv auf die Bilder und Ordner
 *
 * @param unknown_type $project
 * @param unknown_type $id
 * @param unknown_type $params
 * @throws QException
 */
function ajax_media_folder_watermark($project, $id, $params)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); 	 /* @var $Media Media */
	$Folder  = $Media->get( (int)$id );  /* @var $Media MF_Folder */

	$params = Utils_Security_Orthos::clearArray(
	    json_decode($params, true)
	);

	if ($Folder->getType() != 'FOLDER') {
		throw new QException('Diese Funktion steht nur bei Ordnern zur Verfügung');
	}

	$Folder->setWatermark($params);

    QUI::getMessagesHandler()->addSuccess(
        'Das Wasserzeichen wurde erfolgreich auf den Ordner '. $Folder->getAttribute('name') .' gesetzt'
    );
}
$ajax->register('ajax_media_folder_watermark', array('project', 'id', 'params'));

/**
 * Löscht eine ID aus dem Media Center
 *
 * @param String $project
 * @param String / Integer $id
 * @return Bool
 */
function ajax_media_delete($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */
	$Obj     = $Media->get( (int)$id );

	return $Obj->delete();
}
$ajax->register('ajax_media_delete', array('project', 'id'));

/**
 * Zerstört eine ID aus dem Media Center
 *
 * @param String $project
 * @param String / Integer $id
 * @return Bool
 */
function ajax_media_destroy($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */
	$Obj     = $Media->get( (int)$id );

	return $Obj->destroy();
}
$ajax->register('ajax_media_destroy', array('project', 'id'));


/**
 * Aktiviert ein MediaFile
 *
 * @param String $project
 * @param String $id
 * @return unknown
 */
function ajax_media_activate($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia();
	$Obj     = $Media->get( (int)$id );

	return $Obj->activate();
}
$ajax->register('ajax_media_activate', array('project', 'id'));

/**
 * Deaktiviert ein MediaFile
 *
 * @param String $project
 * @param String $id
 * @return unknown
 */
function ajax_media_deactivate($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia();
	$Obj     = $Media->get((int)$id);

	return $Obj->deactivate();
}
$ajax->register('ajax_media_deactivate', array('project', 'id'));

/**
 * Eine Datei wieder herstellen
 *
 * @param String $project
 * @param String $id
 * @param String $pid
 * @return Bool
 */
function ajax_media_restore($project, $id, $pid)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */

	$Child   = $Media->get((int)$id);
	$Parent  = $Media->get((int)$pid);

	return $Child->restore($Parent);
}
$ajax->register('ajax_media_restore', array('project', 'id', 'pid'));

/**
 * Gibt die ParentIds rekursiv zurück
 *
 * @param String $project
 * @param String $id
 * @return Array
 */
function ajax_media_get_parent_ids($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */

	$Obj = $Media->get( (int)$id ); /* @var $Obj MediaFile */
	return $Obj->getParentIds();
}
$ajax->register('ajax_media_get_parent_ids', array('project', 'id'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $id
 * @return unknown
 */
function ajax_media_get_parents($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia();

	$Obj = $Media->get((int)$id);
	$ids = $Obj->getParentIds();

	$result = array();

	foreach ($ids as $id)
	{
		$File     = $Media->get((int)$id);
		$result[] = $File->getAllAttributes();
	}

	return $result;
}
$ajax->register('ajax_media_get_parents', array('project', 'id'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $id
 * @return unknown
 */
function ajax_media_get_path($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia();

	$Obj = $Media->get((int)$id);
	$ids = $Obj->getParentIds();

	$result = array();

	foreach ($ids as $id)
	{
		$File     = $Media->get((int)$id);
		$result[] = $File->getAllAttributes();
	}

	if ($Obj->getType() == 'FOLDER') {
		$result[] = $Obj->getAllAttributes();
	}

	return $result;
}
$ajax->register('ajax_media_get_path', array('project', 'id'));


/**
 * Enter description here...
 *
 * @param String $project
 */
function ajax_media_trash_getsites($project)
{
	$Project = QUI::getProject($project);
	$Trash   = new MC_Trash($Project); /* $Trash MC_Trash */

	$MC_Children = $Trash->getSites(); /* $MC_Children MC_Children */
	return $MC_Children->Arrays();
}
$ajax->register('ajax_media_trash_getsites', array('project'));

/**
 * Zerstört mehrere IDs
 *
 * @param String $project
 * @param String $ids
 * @return Bool
 */
function ajax_media_trash_destroy($project, $ids)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */

	$ids = explode(',', $ids);

	try
	{
		for ($i = 0, $len = count($ids); $i < $len; $i++)
		{
			if ((int)$ids[$i])
			{
				$Child = $Media->get( (int)$ids[$i] ); /* @var $Child MF_File */
				$Child->destroy();
			}
		}

	} catch (QException $e)
	{
		// nothing
	}

	return true;
}
$ajax->register('ajax_media_trash_destroy', array('project', 'ids'));

/**
 * Enter description here...
 *
 * @param String $project
 * @param String $id
 * @param String $type
 * @param String $order
 * @param String $name
 * @return Array
 */
function ajax_media_folder_getchildren($project, $id, $mtype, $order, $filename)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */

	$mtype = Utils_String::JSString($mtype);
	$order = Utils_String::JSString($order);
	$name  = PT_Bool::JSBool($filename);

	$Obj = $Media->get( (int)$id ); /* @var $Obj MF_Folder */
	return $Obj->getChildren($mtype, $order, $name, false)->Arrays();
}
$ajax->register('ajax_media_folder_getchildren', array('project', 'id', 'mtype', 'order', 'filename'));

/**
 * Enter description here...
 *
 * @param String $project
 * @param String $id
 */
function ajax_media_folder_haschildren($project, $id)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia(); /* $Media Media */
	$Obj     = $Media->get( (int)$id ); /* @var $Obj MF_Folder */

	if ($Obj->getType() == 'FOLDER') {
		return $Obj->hasChildren();
	}

	return false;
}
$ajax->register('ajax_media_folder_haschildren', array('project', 'id'));

/**
 * Media Suchtemplate
 *
 * @return String
 */
function ajax_media_search_template()
{
    return QUI_Template::getEngine(true)->fetch(
        SYS_DIR .'template/media_search.html'
    );
}
$ajax->register('ajax_media_search_template');

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $search
 * @param unknown_type $params
 */
function ajax_media_search($project, $search, $params)
{
	$Project = QUI::getProject($project);
	$Media   = $Project->getMedia();
	$params  = json_decode($params, true);

	return $Media->search($search, $params)->Arrays();
}
$ajax->register('ajax_media_search', array('project', 'search', 'params'));

?>