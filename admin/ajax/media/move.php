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

QUI::$Ajax->registerFunction(
    'ajax_media_move',
    function ($project, $to, $ids) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get($to);

        $ids = json_decode($ids, true);

        if (!QUI\Projects\Media\Utils::isFolder($Folder)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.move.is.no.folder')
            );
        }

        /* @var $Folder \QUI\Projects\Media\Folder */
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
