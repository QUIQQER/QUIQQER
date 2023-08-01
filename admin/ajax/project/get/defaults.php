<?php

/**
 * Return the default configuration of a project
 *
 * @param String $project - Project data
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_project_get_defaults',
    function ($project) {
        $Project = QUI\Projects\Manager::decode($project);
        return QUI\Projects\Manager::getProjectConfigList($Project);
    },
    ['project'],
    'Permission::checkAdminUser'
);
