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
QUI::$Ajax->registerFunction(
    'ajax_media_folder_firstImage',
    function ($project, $folderId) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $File    = $Media->get($folderId);

        if (!QUI\Projects\Media\Utils::isFolder($File)) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.no.folder.given'
            ]);
        }

        return $File->firstImage()->getAttributes();
    },
    ['project', 'folderId'],
    'Permission::checkAdminUser'
);
