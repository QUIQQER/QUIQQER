<?php

/**
 * Set the config of an project
 */

function ajax_project_set_config($project, $params)
{
    \QUI\Projects\Manager::setConfigForProject( $project, $params );

    $Project->setup();
}

\QUI::$Ajax->register(
    'ajax_project_set_config',
    array( 'project', 'params' ),
    'Permission::checkAdminUser'
);
