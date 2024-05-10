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

QUI::$Ajax->registerFunction(
    'ajax_media_rename',
    static function ($project, $id, $newname) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Item = $Media->get($id);

        $Item->rename($newname);

        return $Item->getAttribute('name');
    },
    ['project', 'id', 'newname'],
    'Permission::checkAdminUser'
);
