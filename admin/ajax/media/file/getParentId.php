<?php

/**
 * Returns the Parent-ID from a media file
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID
 *
 * @return integer
 */
QUI::$Ajax->registerFunction(
    'ajax_media_file_getParentId',
    function ($project, $fileid) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();

        try {
            $File = $Media->get($fileid);

        } catch (QUI\Exception $Exception) {
            return $Media->firstChild()->getId();
        }

        if (QUI\Projects\Media\Utils::isFolder($File)) {
            return $File->getId();
        }

        try {
            return $File->getParent()->getId();

        } catch (QUI\Exception $Exception) {
            return $Media->firstChild()->getId();
        }
    },
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
