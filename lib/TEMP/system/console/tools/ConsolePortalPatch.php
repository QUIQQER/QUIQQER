<?php

/**
 * This file contains the ConsolePortalPatch
 */

/**
 * Patch für altes Portal
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2008 PCSG
 * @version    0.1 $Revision: 2389 $
 * @since      Class available since Release P.MS 0.15
 *
 * @todo noch einmal anschauen, maybe überdenken
 */

class ConsolePortalPatch extends System_Console_Tool
{
    /**
     * Enter description here...
     *
     * @param unknown_type $params
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $help = " Beschreibung:\n";
        $help .= " Patch für das Portalplugin\n";
        $help .= "\n";
        $help .= " Aufruf:\n";
        $help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleTranslate [params]\n";
        $help .= "\n";
        $help .= " Parameter:\n";
        $help .= " --project=[PROJECT]		Projektnamen\n\n";
        $help .= " --lang=[LANG]		    Sprache\n\n";

        $help .= " Optionale Parameter:\n";
        $help .= " --help			Dieser Hilfetext\n\n";
        $help .= " --test			Testmodus\n\n";
        $help .= "\n";

        $this->addHelp($help);
    }

    /**
     * Führt das Tool aus
     */
    public function start()
    {
        $params = $this->_params;

        if (!isset($params['--project'])) {
            throw new \QUI\Exception('Es wurde kein Project angegeben');
        }

        if (!isset($params['--lang'])) {
            throw new \QUI\Exception('Es wurde keine Sprache angegeben');
        }

        $db   = \QUI::getDB();
        $test = isset($params['--test']) ? true : false;

        $lang    = $params['--lang'];
        $project = $params['--project'];

        $Project = new \QUI\Projects\Project($project, $lang);
        $sites   = $Project->getSites(array(
            'where' => array(
                'type' => 'base/portal'
            )
        ));

        $table = $Project->getAttribute('db_table');

        /* @var $Site \QUI\Projects\Site */
        foreach ($sites as $Site)
        {
            $result = $db->select(array(
                'from'  => $table,
                'where' => array(
                    'id' => $Site->getId()
                )
            ));

            $content = json_decode($result[0]['content'], true);

            if ($test)
            {
                echo $Site->getId() .' - '. $Site->getAttribute('title') ."\n";
                echo json_encode($content);
                echo "\n\n";
                continue;
            }

            $_Site = new \QUI\Projects\Site\Edit($Project, $Site->getId());

            echo $_Site->getId() .' - '. $_Site->getAttribute('title') ."\n";
            $_Site->setAttribute('pcsg.portal.content', json_encode($content));
            $_Site->setAttribute('type', 'portal/portal');

            $_Site->updateTemp('pcsg.portal.content', json_encode($content));
            $_Site->updateTemp('type', 'portal/portal');

            $_Site->save();
            echo "Status ... OK\n\n";
        }
    }
}

?>