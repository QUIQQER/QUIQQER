<?php

/**
 * This file contains the ConsoleMaintenance
 */

/**
 * Patch System
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2008 PCSG
 * @version    0.12 $Revision: 2389 $
 * @since      Class available since Release P.MS 0.9.8
 *
 * @todo aktualisieren
 */

class ConsoleMaintenance extends System_Console_Tool
{
    /**
     * constructor
     * @param array $params
     */
	public function __construct($params)
	{
		parent::__construct($params);

		$help = " Beschreibung:\n";
		$help .= " Wartungsarbeiten setzen\n";
		$help .= "\n";
		$help .= " Aufruf:\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleMaintenance [params]\n";
		$help .= "\n";
		$help .= " Parameter:\n";
		$help .= " --status=[FILE] Welcher Status soll gesetzt werden\n\n";

		$help .= " Optionale Parameter:\n";
		$help .= " --help			Dieser Hilfetext\n\n";
		$help .= "\n";

		$this->addHelp($help);
	}

	/**
	 * Führt das Tool aus
	 */
	public function start()
	{
		$params = $this->_params;

		if (!isset($params['--status'])) {
			throw new \QUI\Exception('Es wurde keine Status angegeben');
		}

		$Config = QUI::getConfig('etc/conf.ini');
		$Config->setValue('globals','maintenance', (bool)$params['--status']);

		$Config->save();
	}
}

?>