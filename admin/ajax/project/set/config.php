<?php

/**
 * Set the config of an project
 */

function ajax_project_set_config($project, $params)
{
    $params = json_decode( $params, true );

    \QUI\Projects\Manager::setConfigForProject( $project, $params );
}

\QUI::$Ajax->register(
    'ajax_project_set_config',
    array( 'project', 'params' ),
    'Permission::checkAdminUser'
);
