<?php

/**
 * Copy files to a folder
 *
 * @param string $project - Name of the project
 * @param string $to - folder id
 * @param string $ids - ids which copied
 *
 * @throws \QUI\Exception
 */

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_copy',
    static function ($project, $to, $ids): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get($to);

        $ids = json_decode($ids, true);

        if (!QUI\Projects\Media\Utils::isFolder($Folder)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.copy.is.no.folder')
            );
        }

        /* @var $Folder Folder */
        foreach ($ids as $id) {
            try {
                $Item = $Media->get((int)$id);
                $Item->copyTo($Folder);
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );
            }
        }
    },
    ['project', 'to', 'ids'],
    'Permission::checkAdminUser'
);
