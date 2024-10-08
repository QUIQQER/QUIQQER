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
    'ajax_site_unlink',
    static function ($project, $id, $parentId, $all): void {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->deleteLinked((int)$parentId, $all);
    },
    ['project', 'id', 'parentId', 'all'],
    'Permission::checkAdminUser'
);
