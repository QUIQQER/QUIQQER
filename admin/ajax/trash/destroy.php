<?php

/**
 * Destroy site ids
 *
 * @param string $project - Project data, JSON Array
 * @param string $ids - JSON Array, List of site ids
 */

QUI::$Ajax->registerFunction(
    'ajax_trash_destroy',
    static function ($project, $ids): void {
        $Project = QUI::getProjectManager()->decode($project);
        $ids = json_decode($ids, true);
        $Trash = $Project->getTrash();

        $Trash->destroy($ids);
    },
    ['project', 'ids'],
    'Permission::checkAdminUser'
);
