<?php

/**
 * Set the config of an project
 *
 * @param String $project - project name
 * @param String $params - JSON Array
 */
function ajax_project_set_config($project, $params)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $params  = json_decode( $params, true );

    if ( isset( $params['project-custom-css'] ) )
    {
        $Project->setCustomCSS( $params['project-custom-css'] );
        unset( $params['project-custom-css'] );
    }

    \QUI\Projects\Manager::setConfigForProject( $project, $params );
}

\QUI::$Ajax->register(
    'ajax_project_set_config',
    array( 'project', 'params' ),
    'Permission::checkAdminUser'
);
