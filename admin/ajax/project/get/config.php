<?php

/**
 * Return the configuration of the project
 *
 * @param string $project - JSON Project data
 * @param string $param - optional, wanted config
 * @return array|string
 */
function ajax_project_get_config($project, $param)
{
    $Project = QUI\Projects\Manager::getProject($project);

    if (isset($param)) {
        return $Project->getConfig($param);
    }

    return $Project->getConfig();
}

QUI::$Ajax->register(
    'ajax_project_get_config',
    array('project', 'param'),
    'Permission::checkAdminUser'
);
