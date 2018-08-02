<?php

/**
 * Destroy files
 *
 * @param string $project - Name of the project
 * @param string $ids - JSON Array, List of IDs
 */
QUI::$Ajax->registerFunction(
    'ajax_trash_media_destroy',
    function ($project, $ids) {
        $Project = QUI::getProjectManager()->decode($project);
        $Media   = $Project->getMedia();
        $Trash   = $Media->getTrash();

        $ids = json_decode($ids, true);

        foreach ($ids as $id) {
            $Trash->destroy($id);
        }
    },
    ['project', 'ids'],
    'Permission::checkAdminUser'
);
