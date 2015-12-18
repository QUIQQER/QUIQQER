<?php

/**
 * Create a new folder
 *
 * @param string $project - Name of the project
 * @param string $parentid - Parent-ID of the new folder
 * @param string $newfolder - Name of the new Folder
 *
 * @return array
 * @throws \QUI\Exception
 */
function ajax_media_folder_create($project, $parentid, $newfolder)
{
    $Project = QUI\Projects\Manager::getProject($project);
    $Media   = $Project->getMedia();
    $File    = $Media->get($parentid);

    if (QUI\Projects\Media\Utils::isFolder($File) === false) {
        throw new QUI\Exception(
            'Sie können nur in einem Ordner einen Ordner erstellen'
        );
    }

    /* @var $File \QUI\Projects\Media\Folder */
    $Folder = $File->createFolder($newfolder);

    return QUI\Projects\Media\Utils::parseForMediaCenter($Folder);
}

QUI::$Ajax->register(
    'ajax_media_folder_create',
    array('project', 'parentid', 'newfolder'),
    'Permission::checkAdminUser'
);
