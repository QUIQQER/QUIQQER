<?php

/**
 * Move a site under another site
 *
 * @param string $project - project data
 * @param integer $id - site ID
 * @param integer $newParentId - new parent ID
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_site_move',
    static function ($project, $id, $newParentId): void {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->move((int)$newParentId);
    },
    ['project', 'id', 'newParentId'],
    'Permission::checkAdminUser'
);
