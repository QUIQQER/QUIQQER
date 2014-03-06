<?php

/**
 * Erste Seite vom Projekt bekommen
 *
 * @return Array
 */
function ajax_project_parent($project, $lang, $id)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site  = $Project->get( $id );

    if ( !$Site->getParentId() ) {
        return 1;
    }

    return $Site->getParentId();
}

\QUI::$Ajax->register(
    'ajax_project_parent',
    array('project', 'lang', 'id')
);
