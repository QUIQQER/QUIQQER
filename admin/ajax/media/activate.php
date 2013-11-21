<?php

/**
 * Activate the file / files
 *
 * @throws \QUI\Exception
 * @return true - if succeed
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
