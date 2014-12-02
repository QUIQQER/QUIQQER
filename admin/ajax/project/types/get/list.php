<?php

/**
 * Return the sitetypes of the project
 *
 * @param String $project - project data; JSON Array
 * @return Array
 */
function ajax_project_types_get_list($project)
{
    $Project = \QUI::getProjectManager()->decode( $project );

    return \QUI::getPluginManager()->getAvailableTypes( $Project );
}

\QUI::$Ajax->register(
    'ajax_project_types_get_list',
    array( 'project' ),
    'Permission::checkAdminUser'
);
