<?php

/**
 * Alle Seitentypen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return Array
 */
function ajax_project_types_get_list($project)
{
    $Project = false;

    if ( !empty( $project ) ) {
        $Project = \QUI\Projects\Manager::getProject( $project );
    }

    return \QUI::getPluginManager()->getAvailableTypes( $Project );
}

\QUI::$Ajax->register(
    'ajax_project_types_get_list',
    array( 'project' ),
    'Permission::checkAdminUser'
);
