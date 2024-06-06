<?php

/**
 * Create a default structure for a project
 *
 * @param string $project - JSON Project Array
 */

QUI::$Ajax->registerFunction(
    'ajax_project_createDefaultStructure',
    static function ($project): void {
        QUI\Utils\Project::createDefaultStructure(
            QUI::getProjectManager()->decode($project)
        );
    },
    ['project'],
    'Permission::checkAdminUser'
);
