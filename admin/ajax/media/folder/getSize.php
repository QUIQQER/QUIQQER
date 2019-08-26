<?php

/**
 * Return the size of a folder
 *
 * @param string $project - Name of the project
 * @param string $id - ID of the folder
 *
 * @return array
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_media_folder_getSize',
    function ($project, $id) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $Folder  = $Media->get($id);

        if (QUI\Projects\Media\Utils::isFolder($Folder) === false) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.file.not.found')
            );
        }

        return $Folder->getSize();
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
