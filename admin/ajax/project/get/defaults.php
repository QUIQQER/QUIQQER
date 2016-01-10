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
        $config  = QUI\Projects\Manager::getProjectConfigList($Project);

        return $config;
    },
    array('project'),
    'Permission::checkAdminUser'
);
