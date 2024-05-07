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

QUI::$Ajax->registerFunction(
    'ajax_media_folder_recursiveEffects',
    function ($project, $folderId) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get($folderId);

        if (QUI\Projects\Media\Utils::isFolder($Folder) === false) {
            throw new QUI\Exception(
                'You can create a folder only in a folder'
            );
        }

        /* @var $Folder QUI\Projects\Media\Folder */
        $Folder->setEffectsRecursive();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()
                ->get('quiqqer/core', 'message.folder.effects.resursive,success')
        );
    },
    ['project', 'folderId'],
    'Permission::checkAdminUser'
);
