<?php

/**
 * Rename a media item
 *
 * @param string $project - Name of the project
 * @param string $id - File-ID
 * @param string $newname - new name
 *
 * @return array
 */
function ajax_media_rename($project, $id, $newname)
{
    $Project = QUI\Projects\Manager::getProject($project);
    $Media   = $Project->getMedia();
    $Item    = $Media->get($id);

    $Item->rename($newname);

    return $Item->getAttribute('name');
}

QUI::$Ajax->register(
    'ajax_media_rename',
    array('project', 'id', 'newname'),
    'Permission::checkAdminUser'
);
