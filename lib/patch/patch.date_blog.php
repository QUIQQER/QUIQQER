<?php

/**
 * This file contains the patch_date_blog
 */

/**
 * Patcht die Releasedates von Blog in das Hauptobjekt
 * Release Date Funktion wurde in den Standard übernommen
 * Blog Daten sind somit unnötig
 *
 * @package com.pcsg.qui.console.patch
 * @author www.pcsg.de (Henning Leutz)
 *
 * @param ConsolePatch $Patch
 * @return unknown
 *
 * @deprecated
 */

function patch_date_blog( $Patch )
{
	$Patch->write('Patch Blog Release Dates wird ausgeführt...');

	$db       = \QUI::getDB();
	$projects = Projects_Manager::getProjects();

	$ERRORS = 0;

	foreach($projects as $project => $val)
	{
		try
		{
			$Patch->write('');
			$Patch->write('[BEGIN] Starte mit Projekt: '.$project);
			$Project = \QUI::getProject($project);
			$langs = $Project->getAttribute('langs');

		} catch(\QUI\Exception $e)
		{
			$Patch->write($e->getMessage());
			continue;
		}

		// Jeder Sprache muss gepatcht werden
		foreach($langs as $lang)
		{
			$Patch->write('====> Starte mit Sprache :'.$lang);
			$_Project = \QUI::getProject($project, $lang);

			// Blog Sites
			/*
			$blog_entrys = $_Project->getSitesIds(array(
				'where' => array(
					'type' => 'blog/entry'
				)
			));
			*/

			$tpl = $_Project->getAttribute('name') .'_'. $lang .'_blog';

			$blog_entrys = $db->select(array(
				'from' => $tpl
			));

			foreach($blog_entrys as $entry_id)
			{
				try
				{
					$Site = new Projects_Site_Edit($_Project, $entry_id['id']);

					$Patch->write('Seite '.$Site->getId().' - '.$Site->getAttribute('name').' wird bearbeitet');

					//$r_untl = $Site->getAttribute('pcsg.blog.release_until');
					//$r_from = $Site->getAttribute('pcsg.blog.release_from');

					$r_untl = $entry_id['release_until'];
					$r_from = $entry_id['release_from'];

					//$Patch->write('::'.$r_untl.'--'.$r_from.'::');

					if($r_from)
					{
						if($r_from==0) {
							$r_from = NULL;
						}

						$Site->setAttribute('pcsg.base.release_from', $r_from);
					} else
					{
						$Site->setAttribute('pcsg.base.release_from', NULL);
					}

					if($r_untl)
					{
						if($r_untl==0) {
							$r_untl = NULL;
						}

						$Site->setAttribute('pcsg.base.release_until', $r_untl);
					} else
					{
						$Site->setAttribute('pcsg.base.release_until', NULL);
					}

					$Site->updateTemp(array(
						'pcsg.base.release_from' => $r_from,
						'pcsg.base.release_until' => $r_untl
					));

					$Site->save(false); // Speichern ohne Archiv zu erstellen
					$Site->deleteTemp();

					$Patch->write('OK - Bearbeitung erfolgreich');

				} catch( \QUI\Exception $e )
				{
					$ERRORS++;
					$Patch->write('### Error ### '. $e->getMessage());
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

?>