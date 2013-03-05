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
 * @param unknown_type $id
 * @param unknown_type $lang
 * @param unknown_type $project_name
 * @return unknown
 */
function ajax_multilingual_manager($id, $lang, $project_name)
{
	$Smarty = QUI_Template::getEngine(true);
	$file   = SYS_DIR .'template/multilingual_manager.html';

	try
	{
		$Project = QUI::getProject($project_name, $lang);
		$Site    = new Projects_Site_Edit($Project, (int)$id);

		$config = $Project->getAttribute('config');
		$langs  = explode(',', $config['langs']);

		$Smarty->assign(array(
			'Project' 	=> $Project,
			'Site'		=> $Site,
			'langs'		=> $langs
		));

	} catch (QException $e)
	{
		$Smarty->assign('message', $e->getMessage());
	} catch (Exception $e)
	{
		$Smarty->assign('message', $e->getMessage());
	}

	return $Smarty->fetch($file);
}
$ajax->register('ajax_multilingual_manager', array('id', 'lang', 'project_name'));

/**
 * Enter description here...
 *
 * @param unknown_type $project_name
 * @param unknown_type $id1
 * @param unknown_type $lang1
 * @param unknown_type $id2
 * @param unknown_type $lang2
 */
function ajax_multilingual_addlink($project_name, $id1, $lang1, $id2, $lang2)
{
	$Project = QUI::getProject($project_name, $lang1);
	$Site    = new Projects_Site_Edit($Project, (int)$id1);

	return $Site->addLanguageLink($lang2, $id2);
}
$ajax->register('ajax_multilingual_addlink', array('project_name', 'id1', 'lang1', 'id2', 'lang2'));

/**
 * Enter description here...
 *
 * @param unknown_type $project_name
 * @param unknown_type $id1
 * @param unknown_type $lang1
 * @param unknown_type $id2
 * @param unknown_type $lang2
 */
function ajax_multilingual_removelink($project_name, $id1, $lang1, $id2, $lang2)
{
	$Project = QUI::getProject($project_name, $lang1);
	$Site    = new Projects_Site_Edit($Project, (int)$id1);

	return $Site->removeLanguageLink($lang2);
}
$ajax->register('ajax_multilingual_removelink', array('project_name', 'id1', 'lang1', 'id2', 'lang2'));

/**
 * Enter description here...
 *
 * @param unknown_type $project_name
 * @param unknown_type $id
 * @param unknown_type $sitelang
 * @param unknown_type $parentid
 * @param unknown_type $parentlang
 * @return unknown
 */
function ajax_multilingual_copy($project_name, $id, $sitelang, $parentid, $parentlang)
{
	try
	{
		$p      = QUI::getProject($project_name, $sitelang); // aktuelles Projekt
		$p_lang = QUI::getProject($project_name, $parentlang); // aktuelles Projekt mit anderer Sprache

		$site       = new Projects_Site_Edit($p, (int)$id, false, true); // Aktuelle Seite
		$attributes = $site->getAllAttributes(); // Attribute von aktueller Seite bekommen

		$p_site = new Projects_Site_Edit($p_lang, (int)$parentid, $parentlang, true); // Parent
		$newid  = $p_site->createChild(); // Neues Kind erzeugen

		if (!$newid)
		{
			throw new QException(
				'Konnte kein Kind in anderer Sprache anlegen unter '. $p_lang->getAttribute('name') .'-'. $p_lang->getId()
			);

			return false;
		}

		$newsite = new Projects_Site_Edit($p_lang, (int)$newid, false); // Neues Kind als Objekt
		$newsite->updateTemp($attributes); // Alle Attribute in das neue Kind schmeisen
		$newsite->save(); // Speichern

		ajax_multilingual_addlink($project_name, $id, $sitelang, $newid, $parentlang); // Multilingual Link setzen, damit Verknüpfung vorhanden ist
		return true;

	} catch (QException $e)
	{
		return false;
	} catch (Exception $e)
	{
		return false;
	}
}
$ajax->register('ajax_multilingual_copy', array('project_name', 'id', 'sitelang', 'parentid', 'parentlang'));


?>