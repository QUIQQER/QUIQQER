<?php

/**
 * Copy files to a folder
 *
 * @param string $project - Name of the project
 * @param string $to - Folder-ID
 * @param string $ids - ids which copied
 *
 * @throws \QUI\Exception
 */

use QUI\Projects\Manager;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Utils;

QUI::$Ajax->registerFunction(
    'ajax_media_move',
    static function ($project, $to, $ids) {
        $Project = Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get($to);

        $ids = json_decode($ids, true);

        if (!Utils::isFolder($Folder)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.move.is.no.folder')
            );
        }

        /* @var $Folder Folder */
        foreach ($ids as $id) {
            try {
                $Item = $Media->get((int)$id);
                $Item->moveTo($Folder);
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
