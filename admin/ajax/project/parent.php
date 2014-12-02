<?php

/**
 * Return the parent ID of a site
 *
 * @param String $project - Project data; JSON Array
 * @param String|Integer $id - Site-ID
 * @return Array
 */
function ajax_project_parent($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site  = $Project->get( $id );

    if ( !$Site->getParentId() ) {
        return 1;
    }

    return $Site->getParentId();
}

\QUI::$Ajax->register(
    'ajax_project_parent',
    array('project', 'id')
);
