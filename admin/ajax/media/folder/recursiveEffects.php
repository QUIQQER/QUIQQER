<?php

/**
 * Set the folder effects recursive
 *
 * @param string $project - Name of the project
 * @param string $folderId - Folder-ID
 *
 * @return array
 * @throws \QUI\Exception
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_folder_recursiveEffects',
    static function ($project, $folderId): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get($folderId);

        if (!($Folder instanceof Folder)) {
            throw new QUI\Exception(
                'You can create a folder only in a folder'
            );
        }

        $Folder->setEffectsRecursive();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()
                ->get('quiqqer/core', 'message.folder.effects.resursive,success')
        );
    },
    ['project', 'folderId'],
    'Permission::checkAdminUser'
);
