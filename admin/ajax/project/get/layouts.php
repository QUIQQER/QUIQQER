<?php

/**
 * Return the all layouts of the project
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_project_get_layouts',
    function ($project) {
        $Project = QUI\Projects\Manager::decode($project);

        return $Project->getLayouts();
    },
    array('project'),
    'Permission::checkAdminUser'
);
