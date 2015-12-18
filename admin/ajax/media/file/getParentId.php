<?php

/**
 * Returns the Parent-ID from a media file
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID
 *
 * @return integer
 */
function ajax_media_file_getParentId($project, $fileid)
{
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
}

QUI::$Ajax->register(
    'ajax_media_file_getParentId',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
