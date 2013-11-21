<?php

/**
 * Create a new project
 *
 * @param Array $params
 */
function ajax_project_create($params)
{
    $params = json_decode( $params, true );

    \QUI\Projects\Manager::createProject(
        $params['project'],
        $params['lang'],
        $params['template']
    );
}

\QUI::$Ajax->register(
    'ajax_project_create',
    array( 'params' ),
    'Permission::checkAdminUser'
);
