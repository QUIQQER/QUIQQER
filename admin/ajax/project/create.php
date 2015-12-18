<?php

/**
 * Create a new project
 *
 * @param string $params - JSON Array
 * @return string - Name of the project
 */
function ajax_project_create($params)
{
    $params = json_decode($params, true);

    $Project = QUI\Projects\Manager::createProject(
        $params['project'],
        $params['lang']
    );

    return $Project->getName();
}

QUI::$Ajax->register(
    'ajax_project_create',
    array('params'),
    'Permission::checkAdminUser'
);
