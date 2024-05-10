<?php

/**
 * Return the site types of the project
 *
 * @param string $project - project data; JSON Array
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_project_types_get_list',
    static function ($project) {
        try {
            $Project = QUI::getProjectManager()->decode($project);
        } catch (QUI\Exception) {
            $Project = false;
        }

        return QUI::getPackageManager()->getAvailableSiteTypes();
    },
    ['project'],
    'Permission::checkAdminUser'
);
