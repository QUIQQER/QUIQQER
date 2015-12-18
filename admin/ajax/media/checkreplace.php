<?php

/**
 * Checks, if a replacement of the file can be executed
 *
 * @param string $project - Name of the project
 * @param integer $fileid - File-ID
 * @param string $filename - File name
 * @param string $filetype - File type
 */
function ajax_media_checkreplace($project, $fileid, $filename, $filetype)
{
    $Project = QUI\Projects\Manager::getProject($project);
    $Media   = $Project->getMedia();

    // check before upload if a replacement is allowed
    QUI\Projects\Media\Utils::checkReplace($Media, $fileid, array(
        'name' => $filename,
        'type' => $filetype
    ));
}

QUI::$Ajax->register(
    'ajax_media_checkreplace',
    array('project', 'fileid', 'filename', 'filetype'),
    'Permission::checkAdminUser'
);
