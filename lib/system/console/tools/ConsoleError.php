<?php

/**
 * This file contains the ConsoleError
 */

/**
 * Die Error.log an die Error Mail senden
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2008 PCSG
 * @version    0.1 $Revision: 3440 $
 * @since      Class available since Release P.MS 0.9.8
 *
 * @todo aktualisieren
 */

class ConsoleError extends System_Console_Tool
{
    /**
     * constructor
     * @param array $params
     */
	public function __construct($params)
	{
		parent::__construct($params);

		$help = " Beschreibung\n";
		$help .= " Sendet die Error.log des heutigen Tages an die Error Mail\n";
		$help .= "\n";
		$help .= " Aufruf\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleError\n";
		$help .= "\n";
		$help .= " Optionale Argumente\n";
		$help .= " --help		Dieser Hilfetext\n";
		$help .= "\n";

		$this->addHelp($help);
	}

	/**
	 * Führt das Tool aus
	 */
	public function start()
	{
		if (!ERROR_MAIL)
		{
            $this->message( "Es ist keine Mail hinterlegt an welche die error.log gesendet werden könnte");
		    return;
		}

		$log  = VAR_DIR.'log/error'. date('-Y-m-d') .'.log';
		$Mail = new QUI_Mail();

		$this->message("Mail wird vorbereitet\n");

		if (file_exists($log) && filesize($log) < 20000000)
		{
			$Mail->send(array(
				'MailTo' 	=> ERROR_MAIL,
				'Subject' 	=> 'CRON: Fehlermeldungen '. date('Y-m-d') .' auf '. HOST,
				'Body' 		=> 'error'. date('-Y-m-d') .'.log',
				'IsHTML' 	=> false,
				'files'		=> array($log)
			));
		} else if(file_exists($log))
		{
			$Mail->send(array(
				'MailTo' 	=> ERROR_MAIL,
				'Subject' 	=> 'CRON: Fehlermeldungen '. date('Y-m-d') .' auf '. HOST .' (ERROR LOG zu gross)',
				'Body' 		=> 'error'. date('-Y-m-d') .'.log',
				'IsHTML' 	=> false
			));
		} else
		// keine Error.log vorhanden
		{
			$Mail->send(array(
				'MailTo' 	=> ERROR_MAIL,
				'Subject' 	=> 'CRON: Fehlermeldungen '. date('Y-m-d') .' auf '. HOST,
				'Body' 		=> 'Es wurden keine Errors geschrieben',
				'IsHTML' 	=> false
			));
		}

		$this->message("Mail wurde versendet\n");
	}
}

?>