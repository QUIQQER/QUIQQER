<?php

/**
 * Create a new project
 *
 * @param string $params - JSON Array
 * @return string - Name of the project
 */
QUI::$Ajax->registerFunction(
    'ajax_project_create',
    function ($params) {
        $params = json_decode($params, true);

        $Project = QUI\Projects\Manager::createProject(
            $params['project'],
            $params['lang']
        );

        return $Project->getName();
    },
    ['params'],
    'Permission::checkAdminUser'
);
