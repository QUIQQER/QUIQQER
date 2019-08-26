<?php

/**
 * Destroy all files
 *
 * @param string $project - Name of the project
 */
QUI::$Ajax->registerFunction(
    'ajax_trash_media_clear',
    function ($project) {
        $Project = QUI::getProjectManager()->decode($project);
        $Media   = $Project->getMedia();
        $Trash   = $Media->getTrash();
        $Trash->clear();
    },
    ['project'],
    'Permission::checkAdminUser'
);
