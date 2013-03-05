<?php

/**
 * This file contains QUI_Events_Manager
 */

/**
 * The Event Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.events
 */
class QUI_Events_Manager
{
    /**
     * registered events
     * @var array
     */
	static $events = array();

	/**
	 * i think its unnecessary, js files?
	 * @var array
	 */
    private $_js = array();

	/**
	 * Add a event
	 *
	 * @param String $eventname - on*
	 * @param QUI_Events_Event|function $Call
	 */
	static function add($eventname, $Call)
	{
	    if (!isset(self::$events[ $eventname ])) {
	        self::$events[ $eventname ] = array();
	    }

	    self::$events[ $eventname ][] = $Call;
	}

	/**
	 * Fires a event and exec all registered events/functions
	 *
	 * @param String $eventname - Name of the event
	 * @param Array $params 	- Parameters for the event
	 *
	 * @return array - the results of the events
	 */
	static function fire($eventname, $params=array())
	{
	    if (!isset(self::$events[ $eventname ])) {
            return;
	    }

	    $events  = self::$events[ $eventname ];
	    $returns = array();

	    foreach ($events as $Call)
	    {
            if (get_class($Call) === 'Closure')
            {
                $returns[] = $Call($params);
                continue;
            }

            if (method_exists($Call, 'fire')) {
                $returns[] = $Call->fire($params);
            }
	    }

	    return $returns;
	}

	/**
	 * Delete the event, clear all functions and objects to it
	 *
	 * @param String $eventname - the name of the event
	 */
	static function remove($eventname)
	{
        unset(self::$events[ $eventname ]);
	}

	/**
	 * Return a complete list of registered events
	 * for development
	 *
	 * @return Array
	 */
	static function getList()
	{
	    $result = array();

	    foreach ($result as $key => $ev) {
            $result[] = $key;
	    }

        return $result;
	}



    /**
     * everything whats following is depricated
     */
	/**
	 * Ein JavaScript File beim Laden des Adminbereichs laden
	 *
	 * @param String $js - Pfad zur JavaScript Datei
	 * @deprecated
	 */
	public function loadFile($js)
	{
		$this->_js[] = $js;
	}

	/**
	 * Gibt die Files welche geladen werden sollen zurück
	 *
	 * @return Array
	 * @deprecated
	 */
	public function getFiles()
	{
		return $this->_js;
	}

	/**
	 * Erstellt Ordnerstruktur für Events
	 * @deprecated
	 */
	static function setup()
	{
		$folder = VAR_DIR .'events/';

		Utils_System_File::mkdir($folder .'onstart');
		Utils_System_File::mkdir($folder .'onrequest');
		Utils_System_File::mkdir($folder .'site_create');
	}

	/**
	 * Fügt ein Event dem EventManager hinzu
	 *
	 * @param String $event
	 * @param String $file
	 * @param Object $Plugin
	 * @deprecated
	 */
	static function addEvent($event, $file, $Plugin)
	{
		if (!file_exists($file)) {
			return;
		}

		$folder  = VAR_DIR .'events/';

		switch ($event)
		{
			case 'onstart':
			case 'onrequest':
			case 'site_create':
				$plgfile = $folder . $event .'/'. $Plugin->__toString() .'.php';

				if (file_exists($plgfile)) {
					unlink($plgfile);
				}

				Utils_System_File::copy($file, $plgfile);
			break;

			default:
				return;
			break;
		}
	}

	/**
	 * Führt Events aus
	 *
	 * @param unknown_type $event
	 * @param Bool $include - Events includieren
	 *
	 * @return Array
	 * @deprecated
	 */
	static function load($event, $include=true)
	{
		global $Project, $Site;

		$folder = VAR_DIR .'events/';

		if (!is_dir($folder)) {
			return array();
		}

		switch ($event)
		{
			case 'onstart':
			case 'onrequest':
            case 'site_create':
				$dir = $folder . $event .'/';
			break;

			default:
				return array();
		}

		$files  = Utils_System_File::readDir($dir);
		$result = array();

		foreach ($files as $file)
		{
		    $result[] = $dir . $file;

		    if ($include) {
			    include $dir . $file;
		    }
		}

		return $result;
	}
}

?>