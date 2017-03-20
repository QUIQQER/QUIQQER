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
            throw new QUI\Exception(array(
                'quiqqer/quiqqer',
                'exception.no.folder.given'
            ));
        }

        try {
            /* @var $File \QUI\Projects\Media\Folder */
            return $File->firstImage()->getAttributes();
        } catch (QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addError(
                $Exception->getMessage()
            );

            return array();
        }
    },
    array('project', 'folderId'),
    'Permission::checkAdminUser'
);
