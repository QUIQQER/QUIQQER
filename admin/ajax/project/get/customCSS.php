<?php

/**
 * Return the custom css of the project
 * @return String
 */

function ajax_project_get_customCSS($project)
{
    $Project = \QUI\Projects\Manager::decode( $project );

    return $Project->getCustomCSS();
}

\QUI::$Ajax->register(
    'ajax_project_get_customCSS',
    array( 'project' ),
    'Permission::checkAdminUser'
);
