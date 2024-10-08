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
    static function ($project, $fileid): int {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $fileid = (int)$fileid;

        try {
            $File = $Media->get($fileid);
        } catch (QUI\Exception) {
            return $Media->firstChild()->getId();
        }

        if (QUI\Projects\Media\Utils::isFolder($File)) {
            return $File->getId();
        }

        try {
            return $File->getParent()->getId();
        } catch (QUI\Exception) {
            return $Media->firstChild()->getId();
        }
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
