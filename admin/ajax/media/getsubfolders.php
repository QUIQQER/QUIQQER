<?php

/**
 * Returns the children folders
 *
 * @param string $project - Name of the project
 * @param string $fileid - FileID
 *
 * @return array
 * @throws \QUI\Exception
 */
function ajax_media_getsubfolders($project, $fileid)
{
    $Project = QUI\Projects\Manager::getProject($project);
    $Media   = $Project->getMedia();
    $File    = $Media->get($fileid);

    if (!QUI\Projects\Media\Utils::isFolder($File)) {
        throw new QUI\Exception(
            'Bitte wählen Sie ein Ordner aus um die Dateie zu verschieben.'
        );
    }

    /* @var $File \QUI\Projects\Media\Folder */
    $children  = array();
    $_children = $File->getFolders();

    // create children data
    foreach ($_children as $Child) {
        $children[] = QUI\Projects\Media\Utils::parseForMediaCenter($Child);
    }

    return $children;
}

QUI::$Ajax->register(
    'ajax_media_getsubfolders',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
