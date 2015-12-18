<?php

/**
 * Return the sitetypes of the project
 *
 * @param string $project - project data; JSON Array
 * @return array
 */
function ajax_project_types_get_list($project)
{
    try {
        $Project = QUI::getProjectManager()->decode($project);

    } catch (QUI\Exception $Exception) {
        $Project = false;
    }

    return QUI::getPluginManager()->getAvailableTypes($Project);
}

QUI::$Ajax->register(
    'ajax_project_types_get_list',
    array('project'),
    'Permission::checkAdminUser'
);
