<?php

/**
 * Send the file to the browser
 * The file must be opend directly in the browser
 *
 * @param String $project - Name of the project
 * @param String|Integer $fileid - File-ID
 * @throws \QUI\Exception
 */
function ajax_media_file_preview($project, $fileid)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    if ( \QUI\Projects\Media\Utils::isFolder($File) )
    {
        echo 'You cannot preview a Folder';
        exit;
    }

    \QUI\Utils\System\File::fileHeader( $File->getFullPath() );
}

\QUI::$Ajax->register(
    'ajax_media_file_preview',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
