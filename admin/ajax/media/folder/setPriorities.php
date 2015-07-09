<?php

/**
 * Set priorities of the files
 *
 * @param String         $project    - name of the project
 * @param String|Integer $folderId   - Folder-ID
 * @param String         $priorities - JSON Data
 *
 * @throws \QUI\Exception
 */
function ajax_media_folder_setPriorities($project, $folderId, $priorities)
{
    $Project = \QUI\Projects\Manager::getProject($project);
    $Media = $Project->getMedia();
    $Folder = $Media->get($folderId);

    if (!QUI\Projects\Media\Utils::isFolder($Folder)) {
        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/system', 'exception.media.not.a.folder')
        );
    }

    $priorities = json_decode($priorities, true);

    foreach ($priorities as $priority) {
        try {

            $Item = $Media->get($priority['id']);
            $Item->setAttribute('priority', (int)$priority['priority']);
            $Item->save();

        } catch (QUI\Exception $Exception) {

        }
    }
}

QUI::$Ajax->register(
    'ajax_media_folder_setPriorities',
    array('project', 'folderId', 'priorities'),
    'Permission::checkAdminUser'
);
