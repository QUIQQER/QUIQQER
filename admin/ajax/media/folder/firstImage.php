<?php

/**
 * This file contains ajax_media_folder_firstImage
 */

/**
 * Return the children of a media folder
 *
 * @param string $project - Name of the project
 * @param string|integer $folderid - Folder-ID
 * @param string $params - JSON Order Params
 *
 * @return array
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_folder_firstImage',
    static function ($project, $folderId): array {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($folderId);

        if (!($File instanceof Folder)) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.no.folder.given'
            ]);
        }

        return $File->firstImage()->getAttributes();
    },
    ['project', 'folderId'],
    'Permission::checkAdminUser'
);
