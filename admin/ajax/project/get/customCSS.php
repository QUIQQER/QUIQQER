<?php

/**
 * Return the custom css of the project
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_project_get_customCSS',
    static function ($project) {
        $Project = QUI\Projects\Manager::decode($project);

        return $Project->getCustomCSS();
    },
    ['project'],
    'Permission::checkAdminUser'
);
