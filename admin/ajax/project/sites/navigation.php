<?php

/**
 * Search sites in a project
 *
 * @param String $search - search string
 * @param {Array}
 */
function ajax_project_sites_navigation($project, $lang, $id)
{
    $Project = \QUI\Projects\Manager::getProject( $project, $lang );
    $Site    = $Project->get( $id );

    $result = array();
    $list   = $Site->getNavigation();

    foreach ( $list as $Child )
    {
        $result[] = array(
            'id'    => $Child->getAttribute( 'id' ),
            'name'  => $Child->getAttribute( 'name' ),
            'title' => $Child->getAttribute( 'title' ),
            'type'  => $Child->getAttribute( 'type' ),
            'url'   => URL_DIR . $Child->getUrlRewrited(),
            'image_site'    => $Child->getAttribute( 'image_site' ),
            'image_emotion' => $Child->getAttribute( 'image_emotion' ),
            'hasChildren'   => $Child->hasChildren()
        );
    }

    return $result;
}

\QUI::$Ajax->register(
    'ajax_project_sites_navigation',
    array( 'project', 'lang', 'id' )
);
