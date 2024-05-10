<?php

/**
 * Delete a project
 *
 * @param string $project - Project data, JSON Array
 * @throws QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_project_delete',
    static function ($project): void {
        QUI::getProjectManager()->deleteProject(
            QUI::getProjectManager()->decode($project)
        );
    },
    ['project'],
    'Permission::checkSU'
);
