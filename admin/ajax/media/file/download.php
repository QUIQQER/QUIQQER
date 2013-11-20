<?php

/**
 * Send the file to the browser
 * The file must be opend directly in the browser
 *
 * @return Binary
 * @throws \QUI\Exception
 */
function ajax_media_file_download($project, $fileid)
{
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    if ( Projects_Media_Utils::isFolder( $File ) )
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
