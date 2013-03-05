<?php

/**
 * This file contains the ConsoleImageCacheOptimize
 */

/**
 * console setup
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2008 PCSG
 * @version    0.12 $Revision: 2389 $
 * @since      Class available since Release P.MS 0.9.8
 */

class ConsoleSetup extends System_Console_Tool
{
    /**
     * constructor
     * @param unknown_type $params
     */
	public function __construct($params)
	{
		parent::__construct($params);

		$help = " Beschreibung:\n";
		$help .= " Wartungsarbeiten setzen\n";
		$help .= "\n";
		$help .= " Aufruf:\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleSetup [params]\n";
		$help .= "\n";

		$help .= " Optionale Parameter:\n";
		$help .= " --help			Dieser Hilfetext\n\n";
		$help .= "\n";

		$this->addHelp($help);
	}

	/**
	 * Starts the setup
	 */
	public function start()
	{
		QUI_Setup::all();
	}
}

?>