<?php

/**
 * Return the size of a folder
 *
 * @param string $project - Name of the project
 * @param string $id - ID of the folder
 *
 * @return array
 * @throws \QUI\Exception
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_folder_getSize',
    static function ($project, $id): ?int {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get($id);

        if (!($Folder instanceof Folder)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.file.not.found')
            );
        }

        return $Folder->getSize();
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
