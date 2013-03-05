<?php

/**
 * This file contains the ConsoleCron
 */

/**
 * Cron / Dienst über die Konsole
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2011 PCSG
 * @version    0.1 $Revision: 4366 $
 *
 * @todo aktualisieren
 */

class ConsoleCron extends System_Console_Tool
{
    /**
     * constructor
     * @param array $params
     */
	public function __construct($params)
	{
		parent::__construct($params);

		$help = " Beschreibung:\n";
		$help .= " Tool um Crons in der Konsole aufzurufen\n";
		$help .= "\n";
		$help .= " Aufruf:\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleCron [params]\n";
		$help .= "\n";
		$help .= " Parameter:\n";
		$help .= " --cron=[CRON]		Cron welcher ausgeführt werden soll\n";
		$help .= " --list			Listet alle Crons auf\n\n";

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
		$params      = $this->_params;
		$CronManager = new System_Cron_Manager();

		if (isset($params['--list']))
		{
			$crons = $CronManager->getList();

			foreach ($crons as $entry) {
				$this->message(" - ". $entry["cronname"] ."\n");
			}

			return;
		}

		if (!isset($params['--cron'])) {
			return;
		}

		try
		{
			$Cron = $this->_getCron($params['--cron']);
		} catch (QException $e)
		{
			$this->message("Der Cron wurde nicht gefunden\n\n", 'red');
			exit;
		}

		$Cron->execute($params);
	}

	/**
	 * Hohlt das Cron Objekt
	 *
	 * @param String $name - Name des Crons
	 * @return Cron
	 */
	protected function _getCron($name)
	{
		$CronManager = new System_Cron_Manager();
		$crons = $CronManager->getList();

		foreach ($crons as $entry)
		{
			if ($entry["cronname"] != $name) {
				continue;
			}

			$cronfile = OPT_DIR . $entry['Plugin']->getAttribute('name') .'/cron/cron.'. $entry['cronname'] .'.php';

			if (!file_exists($cronfile)) {
				continue;
			}

			require_once $cronfile;

			$class = 'Cron'. ucwords(strtolower($entry['cronname']));

			if (!class_exists($class)) {
				continue;
			}

			return new $class($entry);
		}

		throw new QException('Cron wurde nicht gefunden');
	}
}

?>