<?php

/**
 * Activate the file / files
 *
 * @param String $project - Name of the project
 * @param String|Integer $fileid - File-ID
 * @return Bool
 * @throws \QUI\Exception
 */
function ajax_media_activate($project, $fileid)
{
    $fileid = json_decode( $fileid, true );

    if ( !is_array( $fileid ) ) {
        $fileid = array( $fileid );
    }

    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();

    foreach ( $fileid as $id ) {
        $Media->get( $id )->activate();
    }

    return true;
}

\QUI::$Ajax->register(
    'ajax_media_activate',
    array( 'project', 'fileid' ),
    'Permission::checkAdminUser'
);
