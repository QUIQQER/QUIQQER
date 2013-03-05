<?php

/**
 * This file contains the patch_date_news
 */

/**
 * Patcht die Releasedates von News in das Hauptobjekt
 * Release Date Funktion wurde in den Standard übernommen
 * News Daten sind somit unnötig
 *
 * @package com.pcsg.qui.console.patch
 * @author www.pcsg.de (Henning Leutz)
 *
 * @param $Patch $Patch
 * @return unknown
 *
 * @deprecated
 */

function patch_date_news( $Patch )
{
	$Patch->write('Patch News Release Dates wird ausgeführt...');

	$db       = QUI::getDB();
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

		} catch(QException $e)
		{
			$Patch->write($e->getMessage());
			continue;
		}

		// Base Setup ausführen
		require_once(OPT_DIR .'base/Base.php');

		foreach($langs as $lang)
		{
			$_Project = new Project($project, $lang);

			$Plugin = new Plugin_base();
			$Plugin->setup($_Project, $db);
		}


		// Jeder Sprache muss gepatcht werden
		foreach($langs as $lang)
		{
			$Patch->write('====> Starte mit Sprache :'.$lang);
			$_Project = new Project($project, $lang);

			$tpl = $_Project->getAttribute('name') .'_'. $lang .'_news';

			$news_entrys = $db->select(array(
				'from' => $tpl
			));

			// News Patch

			foreach($news_entrys as $entry_id)
			{
				try
				{
					$Site = new Projects_Site_Edit($_Project, $entry_id['id']);

					$Patch->write('Seite '.$Site->getId().' - '.$Site->getAttribute('name').' wird bearbeitet');

					$r_untl = $entry_id['release_until'];
					$r_from = $entry_id['release_from'];

					//$Patch->write($r_untl.'-'.$r_from);

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

				} catch( QException $e )
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