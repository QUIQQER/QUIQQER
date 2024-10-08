<?php

/**
 * Create a new folder
 *
 * @param string $project - Name of the project
 * @param string $parentid - Parent-ID of the new folder
 * @param string $newfolder - Name of the new Folder
 *
 * @return array
 * @throws \QUI\Exception
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_folder_create',
    static function ($project, $parentid, $newfolder): array {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($parentid);

        if (!($File instanceof Folder)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.create.folder.only.in.folder')
            );
        }

        $Folder = $File->createFolder($newfolder);

        return QUI\Projects\Media\Utils::parseForMediaCenter($Folder);
    },
    ['project', 'parentid', 'newfolder'],
    'Permission::checkAdminUser'
);
