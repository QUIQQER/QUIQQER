<?php

/**
 * Return the sites in the trash
 *
 * @param string $project - Project data, JSON Array
 * @param string $params - JSON Array
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_trash_sites',
    static function ($project, $params): array {
        $Project = QUI::getProjectManager()->decode($project);
        $Trash = $Project->getTrash();

        return $Trash->getList(
            json_decode($params, true)
        );
    },
    ['project', 'params'],
    'Permission::checkAdminUser'
);
