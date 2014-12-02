<?php

/**
 * Set the config of an project
 *
 * @param String $project - project name
 * @param String $params - JSON Array
 */
function ajax_project_set_config($project, $params)
{
    \QUI\Projects\Manager::setConfigForProject(
        $project,
        json_decode( $params, true )
    );
}

\QUI::$Ajax->register(
    'ajax_project_set_config',
    array( 'project', 'params' ),
    'Permission::checkAdminUser'
);
