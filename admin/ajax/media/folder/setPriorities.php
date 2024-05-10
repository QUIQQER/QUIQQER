<?php

/**
 * Set priorities of the files
 *
 * @param string $project - name of the project
 * @param string|integer $folderId - Folder-ID
 * @param string $priorities - JSON Data
 *
 * @throws \QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_media_folder_setPriorities',
    static function ($project, $folderId, $priorities): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get($folderId);

        if (!QUI\Projects\Media\Utils::isFolder($Folder)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.not.a.folder')
            );
        }

        $priorities = json_decode($priorities, true);

        foreach ($priorities as $priority) {
            try {
                $Item = $Media->get($priority['id']);
                $Item->setAttribute('priority', (int)$priority['priority']);
                $Item->save();
            } catch (QUI\Exception) {
            }
        }
    },
    ['project', 'folderId', 'priorities'],
    'Permission::checkAdminUser'
);
