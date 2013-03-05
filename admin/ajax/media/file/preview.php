<?php

/**
 * Send the file to the browser
 * The file must be opend directly in the browser
 *
 * @return Binary
 * @throws QException
 */
function ajax_media_file_preview($project, $fileid)
{
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    if ( Projects_Media_Utils::isFolder($File) )
    {
        echo 'You cannot preview a Folder';
        exit;
    }

    Utils_System_File::fileHeader( $File->getFullPath() );
}
QUI::$Ajax->register('ajax_media_file_preview', array('project', 'fileid'), 'Permission::checkAdminUser');

?>