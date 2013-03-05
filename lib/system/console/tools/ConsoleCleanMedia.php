<?php

/**
 * This file contains the ConsoleCleanMedia
 */

/**
 * Mediabereich bereinigen
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2008 PCSG
 * @version    0.1 $Revision: 2389 $
 * @since      Class available since Release P.MS 0.6
 *
 * @todo aktualisieren
 */

class ConsoleCleanMedia extends System_Console_Tool
{
    /**
     * project lang
     * @var String
     */
	private $_lang;

	/**
	 * project name
	 * @var String
	 */
	private $_project;

    /**
     * exec type, test or not?
     * @var String
     */
	private $_type;

	/**
	 * temp for files
	 * @var array
	 */
	private $_path;

	/**
	 * Media Object
	 * @var Media
	 */
	private $_media;

	/**
	 * constructor
	 *
	 * @param unknown_type $params
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		$help = " Aufruf\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleCleanMedia --project=[PROJECT] --lang=[LANGUAGE] [--PARAMS]\n";
		$help .= "\n";
		$help .= " Erforderliche Argumente\n";
		$help .= " --project	Name des Projektes\n";
		$help .= " --lang		Sprache des Projektes\n";
		$help .= "\n";
		$help .= " Optionale Argumente\n";
		$help .= " --help		Dieser Hilfetext\n";
		$help .= " --type		type=test - Führt die Aktion nicht aus und schreibt nicht in die Datenbank\n";
		$help .= "\n";
		$this->addHelp($help);
	}

	/**
	 * Mediaclean
	 */
	public function start()
	{
		if(isset($this->_params['--project']) && isset($this->_params['--lang']))
		{
			$this->_project = $this->_params['--project'];
			$this->_lang = $this->_params['--lang'];
		} else
		{
			$this->help();
			exit;
		}

		if(isset($this->_params['--type']) && $this->_params['--type'] == 'test')
		{
			$this->_type = 'test';
		}

		echo "Suche Projekt\n";
		try
		{
			$Project = new Projects_Project($this->_project, $this->_lang);
			echo "Projekt : ".$this->_project."\n";
			$this->_media = $Project->getMedia();

			echo "Gehe Mediabereich durch\n\n";
			$this->_recursivMedia(1);

		} catch(QException $e)
		{
			echo "Projekt '".$this->_project."' konnte nicht gefunden werden!!!\n\n";
		}
	}

	/**
	 * Rekursiv alle bilder durchgehen
	 *
	 * @param Integer $id - file id
	 */
	private function _recursivMedia( $id )
	{
		$children = $this->_media->getChildren( $id );
		$mediapath = CMS_DIR.'media/sites/'.$this->_project.'/';

		foreach($children as $child)
		{
			if($child['file'] != $this->_getPathFromMediaId($child['id']))
			{
				echo $child['id'].' : '.$child['file'].' -> '.$this->_getPathFromMediaId($child['id'])."\n";

				if($this->_type != 'test')
				{
					if(is_dir($mediapath.$child['file']) && $child['type'] == 'folder')
					{
						rename($mediapath.$child['file'], $mediapath.$this->_getPathFromMediaId($child['id']));
					}

					$this->_media->updateFile($child['id'], array(
						'file' => $this->_getPathFromMediaId($child['id'])
					));

					if( is_dir($this->_getPathFromMediaId($child['id'])) )
					{
						echo "[OK] Wurde erfolgreich verschoben\n\n";
					} else
					{
						echo "[FALSE] Konnte nicht verschoben werden\n\n";
					}

				} elseif($this->_type != 'test')
				{
					echo "[FALSE] Konnte nicht verschoben werden\n\n";
				}
			}

			if($child['type'] == 'folder')
			{
				$this->_recursivMedia( $child['id'] );
			}
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	private function _getPathFromMediaId( $id )
	{
		if (isset($this->_path[ $id ]))
		{
			return $this->_path[ $id ];
		} else
		{
			$ppath = array();
			$me = $this->_media->getFileInformation( $id );
			$ppath[] = $me['name'];

			while($parent = $this->_media->getParent( $id ))
			{
				if(isset($parent['name']))
				{
					$id = $parent['id'];
					if($id != 1)
					{
						$exp = explode('/', $parent['name']);
						$ppath[] = $exp[ count($exp)-1 ];
					}
				} else
				{
					$id = false;
				}
			}

			$p = array_reverse($ppath);

			$str_path = '';

			for($i = 0, $len = count($p); $i < $len; $i++)
			{
				$str_path .= $p[ $i ].'/';
			}

			if($me['type'] != 'folder')
			{
				$str_path = substr($str_path, 0, -1);
			}

			$this->_path[ $id ] = $str_path;
			return $this->_path[ $id ];
		}
	}
}

?>