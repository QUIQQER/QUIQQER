<?php

/**
 * Replace a file with another file
 *
 * @param String $project - name of the project
 * @param Integer $fileid
 * @param $file
 */
function ajax_media_replace($project, $fileid, $File)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();

    $file = $File->getAttribute('filepath');

    if ( !file_exists( $file ) ) {
        return;
    }

    $Media->replace( $fileid, $file );
}

\QUI::$Ajax->register(
    'ajax_media_replace',
    array('project', 'fileid', 'File'),
    'Permission::checkAdminUser'
);
