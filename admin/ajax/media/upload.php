<?php

/**
 * Upload a file
 *
 * @param unknown_type $project
 * @param unknown_type $file
 */
function ajax_media_upload($project, $parentid, $File)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $Folder  = $Media->get( (int)$parentid );
    $User    = \QUI::getUserBySession();

    if ( $Folder->getType() != 'QUI\\Projects\\Media\\Folder' )
    {
        throw new \QUI\Exception(
            'The parent id is not a folder. You can only upload files to folders'
        );
    }

    $file = $File->getAttribute('filepath');

    if ( !file_exists($file) ) {
        return;
    }

    $Folder->uploadFile( $file );
}

\QUI::$Ajax->register(
    'ajax_media_upload',
    array( 'project', 'parentid', 'File' ),
    'Permission::checkAdminUser'
);
