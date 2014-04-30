<?php

/**
 * Create a new project
 *
 * @param Array $params
 */
function ajax_project_create($params)
{
    $params = json_decode( $params, true );

    $Project = \QUI\Projects\Manager::createProject(
        $params['project'],
        $params['lang'],
        $params['template']
    );

    return $Project->getName();
}

\QUI::$Ajax->register(
    'ajax_project_create',
    array( 'params' ),
    'Permission::checkAdminUser'
);
