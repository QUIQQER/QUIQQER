<?php

/**
 * Return the configuration of the project
 *
 * @param string $project - JSON Project data
 * @param string $param - optional, wanted config
 * @return array|string
 */
QUI::$Ajax->registerFunction(
    'ajax_project_get_config',
    function ($project, $param) {
        $Project = QUI\Projects\Manager::getProject($project);

        if (isset($param)) {
            return $Project->getConfig($param);
        }

        return $Project->getConfig();
    },
    ['project', 'param'],
    'Permission::checkAdminUser'
);
