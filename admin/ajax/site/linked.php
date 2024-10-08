<?php

/**
 * Create a linkage / shortcut
 *
 * @param string $project
 * @param integer $id
 * @param integer $newParentId
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_site_linked',
    static function ($project, $id, $newParentId): void {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->linked((int)$newParentId);
    },
    ['project', 'id', 'newParentId'],
    'Permission::checkAdminUser'
);
