<?php

/**
 * Daten der Seite bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return Array
 */
function ajax_site_get($project, $lang, $id)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    return array(
        'attributes'   => $Site->getAttributes(),
        'has_children' => $Site->hasChildren(),
        'parentid'     => $Site->getParentId()
    );
}

\QUI::$Ajax->register(
    'ajax_site_get',
    array('project', 'lang', 'id'),
    'Permission::checkAdminUser'
);
