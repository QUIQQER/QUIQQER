<?php

/**
 * This file contains the patch_hkl_content2
 */

/**
 * Patcht HKL Content2 in das Content2 Plugin
 *
 * @package com.pcsg.qui.console.patch
 * @author www.pcsg.de (Henning Leutz)
 *
 * @param ConsolePatch $Patch
 * @return unknown
 *
 * @deprecated
 */

function patch_hkl_content2( $Patch )
{
    $Patch->write('Patch für Content2 Plugin wird ausgeführt...');

    $db       = \QUI::getDB();
    $projects = \QUI\Projects\Manager::getProjects();

    $ERRORS = 0;

    foreach($projects as $project => $val)
    {
        try
        {
            $Patch->write('');
            $Patch->write('[BEGIN] Starte mit Projekt: '.$project);
            $Project = new Project($project);
            $langs = $Project->getAttribute('langs');

        } catch(\QUI\Exception $e)
        {
            $Patch->write($e->getMessage());
            continue;
        }

        // Content 2 Setup ausführen
        require_once(OPT_DIR .'content2/Content2.php');

        foreach($langs as $lang)
        {
            $_Project = new Project($project, $lang);

            $Plugin = new Plugin_content2();
            $Plugin->setup($_Project, $db);
        }

        // Jeder Sprache muss gepatcht werden
        foreach($langs as $lang)
        {
            $Patch->write('====> Starte mit Sprache :'.$lang);

            $tbl_hkl = $Project->getAttribute('name').'_'.$lang.'_hkl';
            $tbl_c2 = $Project->getAttribute('name').'_'.$lang.'_content2';

            $entrys = $db->select(array(
                'from' => $tbl_hkl
            ));

            foreach($entrys as $entry)
            {
                $exist = $db->select(array(
                    'from' => $tbl_c2,
                    'where' => array(
                        'id' => $entry['id']
                    )
                ));

                // Eintrag existiert
                if(!isset($exist[0]) && !empty($entry['content2']))
                {
                    $_result = $db->addData($tbl_c2, array(
                        'id' 		=> $entry['id'],
                        'content2'  => $entry['content2'],
                    ));

                    $Patch->write('[OK] '. $entry['id']);
                }
            }

            $Patch->write('====> Sprache '. $lang .' beendet');
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