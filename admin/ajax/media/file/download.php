<?php

/**
 * Send the file to the browser
 * The file must be opend directly in the browser
 *
 * @param String $project - name of the project
 * @param String|Integer $fileid - File-ID
 * @throws \QUI\Exception
 */
function ajax_media_file_download($project, $fileid)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    if ( \QUI\Projects\Media\Utils::isFolder( $File ) )
    {
        echo 'You cannot download a Folder';
        exit;
    }

    \QUI\Utils\System\File::downloadHeader( $File->getFullPath() );
}

\QUI::$Ajax->register(
    'ajax_media_file_download',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
