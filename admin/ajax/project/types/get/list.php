<?php

/**
 * Return the sitetypes of the project
 *
 * @param string $project - project data; JSON Array
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_types_get_list',
    function ($project) {
        try {
            $Project = QUI::getProjectManager()->decode($project);

        } catch (QUI\Exception $Exception) {
            $Project = false;
        }

        return QUI::getPluginManager()->getAvailableTypes($Project);
    },
    array('project'),
    'Permission::checkAdminUser'
);
