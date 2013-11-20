<?php

/**
 * This file contains the patch_user
 */

/**
 * Erzeugt den nobody Benutzer damit die ID 1 nicht mehr vergeben werden kann
 *
 * @param ConsolePatcht $Patch - Patch Object
 *
 * @package com.pcsg.qui.console.patch
 * @author www.pcsg.de (Henning Leutz)
 *
 * @return Bool
 *
 * @deprecated
 */

function patch_user( $Patch )
{
	$db    = QUI::getDB();
	$Users = QUI::getUsers();

	$Patch->write('Patch User wird ausgeführt...');

	try
	{
		$User = $Users->get(1);

		if($User->getName() == 'nobody')
		{
			$Patch->write('nobody existiert bereits. Somit wird Patch abgebrochen');
			return true;
		}

		$Patch->write('Benutzer mit der ID 1 ('.$User->getName().') existiert und ist nicht nobody.');
		$Patch->write($User->getName().' bekommt somit eine neue ID');

		$create = true;

		while($create)
		{
			srand(microtime()*1000000);
	  		$newid = rand(1, 1000000000);

	  		$result = $db->select(array(
				'from' => constant('Users::TABLE'),
				'where' => array(
					'id' => $newid
	  			)
			));

			$create = false;

			if(isset($result[0]) && $result[0]['id']) {
				$create = true;
			}
		}

		$db->updateData(
			constant('Users::TABLE'),
			array('id' => $newid),
			array('id' => 1)
		);

		$Patch->write('Neue ID: '.$newid);

		// Seiten werde gepatcht -c_user und e_user
		$projects = Projects_Manager::getProjects();

		foreach($projects as $project => $val)
		{
			try
			{
				$Patch->write('Projekte werden nach Benutzer ID 1 durchsucht und angepasst...');
				$Patch->write('[BEGIN] Starte mit Projekt: '.$project);
				$Project = new Project($project);
				$langs = $Project->getAttribute('langs');

			} catch(\QUI\Exception $e)
			{
				$Patch->write($e->getMessage());
				continue;
			}

			foreach($langs as $lang)
			{
				$tbl = $Project->getAttribute('name') .'_'. $lang .'_sites';
				$Patch->write('Update Sprache '.$lang.' ('.$tbl.')');

				// c_date
				$c_user = $db->select(array(
					'select' => 'c_user, id',
					'from' => $tbl,
					'where' => array(
						'c_user' => 1
					)
				));

				$len = count($c_user);

				if($len)
				{
					for($i = 0; $i < $len; $i++)
					{
						$db->updateData(
							$tbl,
							array('c_user' => $newid),
							$c_user[$i]['id']
						);

						$Patch->write(' '.$c_user[$i]['id'].' c_user angepasst');
					}
				}

				// e_date
				$e_user = $db->select(array(
					'select' => 'e_user, id',
					'from' => $tbl,
					'where' => array(
						'e_user' => 1
					)
				));

				$len = count($e_user);

				if($len)
				{
					for($i = 0; $i < $len; $i++)
					{
						$db->updateData(
							$tbl,
							array('e_user' => $newid),
							$e_user[$i]['id']
						);

						$Patch->write(' '.$e_user[$i]['id'].' e_user angepasst');
					}
				}
			}

			$Patch->write('[END] Projekt: '.$project);
		}

		$Patch->write('nobody wird nun angelegt ...');
	} catch(\QUI\Exception $e)
	{
		$Patch->write('Benutzer mit der ID 1 existiert nicht. nobody wird angelegt ...');
	}

	$db->addData(
		constant('Users::TABLE'),
		array(
			'id'  => 1,
			'username' => 'nobody',
			'regdate' => '0',
			'password' => Utils_Security_Orthos::getPassword(),
			'active' => 1
		)
	);

	try
	{
		global $Session;
		$_Users = new Users($Session); // Da User den admin noch im Cache hat
		$_User = $_Users->get(1);
		$Patch->write('Benutzer '. $_User->getName() .' wurde angelegt!');

		if(isset($newid))
		{
			$Patch->write('');
			$Patch->write('Falls der Root Benutzer geändert wurde müssen Sie die neue ID ('.$newid.') in etc/conf.ini anpassen');
		}

	} catch(\QUI\Exception $e)
	{
		$Patch->write( $e->getMessage() );
		return false;
	}

	$Patch->write('');
	$Patch->write('');

	return true;
}

?>