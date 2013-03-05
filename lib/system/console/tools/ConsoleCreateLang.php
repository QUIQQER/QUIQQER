<?php

/**
 * This file contains the ConsoleCreateLang
 */

/**
 * Erstellt eine neue Sprache
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2012 PCSG
 * @version    0.1 $Revision: 2389 $
 *
 * @todo aktualisieren
 */

class ConsoleCreateLang extends System_Console_Tool
{
	/**
	 * constructor
	 * @param unknown_type $params
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		$help = " Beschreibung:\n";
		$help .= " Sprache erstellen und Sprachtabelle anlegen\n";
		$help .= "\n";
		$help .= " Aufruf:\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleCreateLang [params]\n";
		$help .= "\n";
		$help .= " Parameter:\n";
		$help .= " --project=[PROJECT]		Projektnamen\n\n";
		$help .= " --newlang=[LANG]		    Neue Sprache die angelegt werden soll\n\n";
		$help .= " --copylang=[LANG]	    Sprache die kopiert werden soll\n\n";

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

		if (!isset($params['--project']))
		{
			$this->message("\nEs wurde kein Project angegeben\n", 'red');
			exit;
		}

		if (!isset($params['--newlang']))
		{
			$this->message("\nEs wurde keine Sprache angegeben\n", 'red');
			exit;
		}

	    $DataBase = QUI::getDB();
        $Config   = QUI::getConfig('etc/projects.ini');

        $langs = explode(',', $Config->get($params['--project'], 'langs'));

        if (in_array($params['--newlang'], $langs))
        {
            $this->message("\nDiese Sprache existiert für das Projekt schon\n", 'red');
            exit;
        }


        $langs[] = $params['--newlang'];

        $Config->set($params['--project'], 'langs', implode(',', $langs));
        $Config->save();


        $Project = QUI::getProject($params['--project']);
        $Project->setup();

        $Plugins = QUI::getPlugins();
        $Plugins->setup($Project);

        $table = $Project->getAttribute('name') .'_'. $params['--newlang'] .'_sites';

        // multilingual tabelle um sprache erweitern
        if (!$DataBase->existTable($Project->getAttribute('name') .'_multilingual'))
        {
            $DataBase->createTable($Project->getAttribute('name') .'_multilingual', array(
                $Project->getAttribute('lang') => 'BIGINT( 2 ) NOT NULL'
            ));
        }


        $fields = $DataBase->getFields($Project->getAttribute('name') .'_multilingual');

        if (!in_array($params['--newlang'], $fields))
        {
            $DataBase->query(
            	'ALTER TABLE `'. $Project->getAttribute('name') .'_multilingual`
            	 ADD `'. $params['--newlang'] .'`
            	 BIGINT( 2 ) NOT NULL'
            );
        }

        if (!isset($params['--copylang']))
        {
            /**
             * Erste Seite anlegen
             */
            $DataBase->query(
            	"INSERT INTO `". $table ."`
                    (`id`, `name`, `title`, `short`, `content`, `type`, `active`, `deleted`, `c_date`, `e_date`, `c_user`, `e_user`, `nav_hide`, `order_type`, `order_field`, `extra`, `c_user_ip`)
                VALUES ('1', 'Startpage', 'Startpage', 'Startpage', NULL, 'standard', '0', '0', NOW(), CURRENT_TIMESTAMP, NULL, NULL, '', NULL, NULL, NULL, NULL);"
            );

            $_Site = new Projects_Site_Edit($Project, 1);
            $_Site->addLanguageLink($params['--newlang'], 1);

            return;
        }

        $from = $Project->getAttribute('name') .'_'. $params['--copylang'] .'_sites';


        $DataBase->query(
             "INSERT INTO `". $Project->getAttribute('name') .'_'. $params['--newlang'] ."_sites`
            SELECT *
            FROM `". $Project->getAttribute('name') .'_'. $params['--copylang'] ."_sites`"
        );

        $DataBase->query(
            "INSERT INTO `". $Project->getAttribute('name') .'_'. $params['--newlang'] ."_sites_relations`
            SELECT *
            FROM `". $Project->getAttribute('name') .'_'. $params['--copylang'] ."_sites_relations`"
        );

        $ids = $Project->getSitesIds();

        foreach ($ids as $id)
        {
            try
            {
                $_Site = new Projects_Site_Edit($Project, $id['id']);
                $_Site->addLanguageLink($params['--newlang'], $id['id']);
            } catch (QException $e)
            {
                $this->message($e->getMessage()."\n", 'red');
            }
        }
	}
}

?>