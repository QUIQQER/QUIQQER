<?php

/**
 * Return the all layouts of the project
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_project_get_layouts',
    function ($project) {
        return QUI\Projects\Manager::decode($project)->getLayouts();
    },
    ['project'],
    'Permission::checkAdminUser'
);
