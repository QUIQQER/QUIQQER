<?php

/**
 * Destroy all files
 *
 * @param string $project - Name of the project
 */

QUI::$Ajax->registerFunction(
    'ajax_trash_media_clear',
    static function ($project): void {
        $Project = QUI::getProjectManager()->decode($project);
        $Media = $Project->getMedia();
        $Trash = $Media->getTrash();
        $Trash->clear();
    },
    ['project'],
    'Permission::checkAdminUser'
);
