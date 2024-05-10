<?php

/**
 * Return the custom css of the project
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_project_get_customJavaScript',
    static function ($project): string {
        $Project = QUI\Projects\Manager::decode($project);

        return $Project->getCustomJavaScript();
    },
    ['project'],
    'Permission::checkAdminUser'
);
