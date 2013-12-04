<?php

/**
 * Media item roh daten bekommen
 *
 * @param String $project
 * @param String $parentid
 *
 * @return Array
 */
function ajax_media_folder_create($project, $parentid, $newfolder)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $parentid );

    if ( \QUI\Projects\Media\Utils::isFolder($File) === false )
    {
        throw new \QUI\Exception(
            'Sie können nur in einem Ordner einen Ordner erstellen'
        );
    }

    /* @var $File \QUI\Projects\Media\Folder */
    $Folder = $File->createFolder( $newfolder );

    return \QUI\Projects\Media\Utils::parseForMediaCenter( $Folder );
}

\QUI::$Ajax->register(
    'ajax_media_folder_create',
    array('project', 'parentid', 'newfolder'),
    'Permission::checkAdminUser'
);
