<?php

/**
 * Search sites in a project
 *
 * @param String $search - search string
 * @param {Array}
 */
function ajax_project_sites_search($project, $lang, $search, $params)
{
    $params  = json_decode( $params, true );
    $Project = \QUI\Projects\Manager::getProject($project, $lang);

    $sites  = $Project->search( $search, $params['fields'] );
    $result = array();

    foreach ( $sites as $Site )
    {
        $result[] = array(
            'id'     => $Site->getId(),
            'name'   => $Site->getAttribute( 'name' ),
            'title'  => $Site->getAttribute( 'title' ),
            'c_date' => $Site->getAttribute( 'c_date' ),
            'c_user' => $Site->getAttribute( 'c_user' ),
            'e_date' => $Site->getAttribute( 'e_date' ),
            'e_user' => $Site->getAttribute( 'e_user' )
        );
    }

    return \QUI\Utils\Grid::getResult( $result, 1, 10 );
}

\QUI::$Ajax->register(
    'ajax_project_sites_search',
    array( 'project', 'lang', 'search', 'params' ),
    'Permission::checkAdminUser'
);
