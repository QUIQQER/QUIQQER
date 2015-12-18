<?php

/**
 * Return the default configuration of a project
 *
 * @param String $project - Project data
 * @return array
 */
function ajax_project_get_defaults($project)
{
    $Project = QUI\Projects\Manager::decode($project);
    $config  = QUI\Projects\Manager::getProjectConfigList($Project);

    return $config;
}

QUI::$Ajax->register(
    'ajax_project_get_defaults',
    array('project'),
    'Permission::checkAdminUser'
);
