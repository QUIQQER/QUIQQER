<?php

/**
 * Destroy all files
 *
 * @param string $project - Name of the project
 */
function ajax_trash_media_clear($project)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Media   = $Project->getMedia();
    $Trash   = $Media->getTrash();
    $Trash->clear();
}

QUI::$Ajax->register(
    'ajax_trash_media_clear',
    array('project'),
    'Permission::checkAdminUser'
);
