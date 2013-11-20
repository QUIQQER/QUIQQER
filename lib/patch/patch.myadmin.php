<?php

/**
 * This file contains the patch_myadmin
 */

/**
 * Patcht Links was phpMyAdmin manchmal reinhaut
 *
 * @package com.pcsg.qui.console.patch
 * @author www.pcsg.de (Henning Leutz)
 *
 * @param ConsolePatch $Patch
 * @return unknown
 *
 * @deprecated
 */

function patch_myadmin( $Patch )
{
	$Patch->write('Patch für phpMyAdmin wird ausgeführt...');

	$db       = \QUI::getDB();
	$projects = Projects_Manager::getProjects();

	$ERRORS = 0;

	foreach($projects as $project => $val)
	{
		try
		{
			$Patch->write('');
			$Patch->write('[BEGIN] Starte mit Projekt: '.$project);
			$Project = new Project($project);
			$langs = $Project->getAttribute('langs');

		} catch(\QUI\Exception $e)
		{
			$Patch->write($e->getMessage());
			continue;
		}

		// Jeder Sprache muss gepatcht werden
		foreach($langs as $lang)
		{
			$Patch->write("\n\n====> Starte mit Sprache :".$lang);

			$_Project = new Project($project, $lang);
			$tpl 	  = $_Project->getAttribute('name') .'_'. $lang .'_base';

			$entrys = $db->select(array(
				'from' => $tpl
			));

			foreach($entrys as $entry)
			{
				try
				{
					$_Site = new Projects_Site_Edit($_Project, $entry['id']);

					$Patch->write(
						'Seite '. $_Site->getId() .' - '.
						$_Site->getAttribute('name') .' wird bearbeitet'
					);

					$content = _phpMyAdmin_parse_content(
						$_Site->getAttribute('content')
					);

					$content2 = _phpMyAdmin_parse_content(
						$_Site->getAttribute('content2')
					);

					$_Site->setAttribute('content', $content);
					$_Site->setAttribute('content2', $content2);

					$_Site->updateTemp(array(
						'content'  => $content,
						'content2' => $content2
					));

					$_Site->save(false); // Speichern ohne Archiv zu erstellen
					$_Site->deleteTemp();

					$Patch->write('OK - Bearbeitung erfolgreich');

				} catch( \QUI\Exception $e )
				{
					$ERRORS++;
					$Patch->write('False- Fehler: '.$e->getMessage());
				}
			}

			$Patch->write('====> Sprache beendet');
		}

		$Patch->write('[END] Projekt beendet');
	}

	$Patch->write('');
	$Patch->write('##### FEHLER '. $ERRORS .' #####');
	$Patch->write('');

	if($ERRORS) {
		return false;
	}

	return true;
}

/**
 * Sucht Links
 *
 * @param unknown_type $content
 * @return unknown
 *
 * @package com.pcsg.qui.console.patch.phpmyadmin
 */
function _phpMyAdmin_parse_content( $content )
{
	$content = preg_replace_callback(
		'#(href|src|action|value|data)="([^"]*)"#',
		"_clean_links_from_phpmyadmin",
		$content
	);

	return $content;
}

/**
 * Prüft Links
 *
 * @param unknown_type $links
 * @return unknown
 *
 * @package com.pcsg.qui.console.patch.phpmyadmin
 */
function _clean_links_from_phpmyadmin( $links )
{
	$links = str_replace('&amp;','&', $links); // &amp; fix
	$links = str_replace('〈=','&lang=',$links); // URL FIX

	$_link = $links[2];

	// Falls kein phpMyAdmin vorhanden ist
	if(strpos($_link, 'phpMyAdmin') === false) {
		return $links[1].'="'.$_link.'"';
	}

	$_link = preg_replace('#&phpMyAdmin([^"|^&]*)#', '', $_link);
	$_link = preg_replace('#?phpMyAdmin([^"|^&]*)#', '', $_link);

	return $links[1].'="'.$_link.'"';
}

?>