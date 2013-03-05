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
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $parentid );

    if ( Projects_Media_Utils::isFolder($File) === false ) {
        throw new QException('Sie können nur in einem Ordner einen Ordner erstellen');
    }

    /* @var $File Projects_Media_Folder */
    $Folder = $File->createFolder( $newfolder );

    return Projects_Media_Utils::parseForMediaCenter( $Folder );
}

QUI::$Ajax->register(
	'ajax_media_folder_create',
    array('project', 'parentid', 'newfolder'),
    'Permission::checkAdminUser'
);

?>