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
function ajax_media_folder_recursiveEffects($project, $folderId)
{
    $Project = QUI\Projects\Manager::getProject($project);
    $Media   = $Project->getMedia();
    $Folder  = $Media->get($folderId);

    if (QUI\Projects\Media\Utils::isFolder($Folder) === false) {
        throw new QUI\Exception(
            'Sie können nur in einem Ordner einen Ordner erstellen'
        );
    }

    /* @var $Folder QUI\Projects\Media\Folder */
    $Folder->setEffectsRecursive();

    QUI::getMessagesHandler()->addSuccess(
        QUI::getLocale()
            ->get('quiqqer/system', 'message.folder.effects.resursive,success')
    );
}

QUI::$Ajax->register(
    'ajax_media_folder_recursiveEffects',
    array('project', 'folderId'),
    'Permission::checkAdminUser'
);
