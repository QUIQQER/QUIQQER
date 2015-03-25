<?php

/**
 * Restore media files
 *
 * @param String $project - Name of the project
 * @param String $ids - JSON Array, File IDs
 * @param String|Integer $parentid - Folder-ID
 */
function ajax_trash_media_restore($project, $ids, $parentid)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Media   = $Project->getMedia();
    $Trash   = $Media->getTrash();
    $Folder  = $Media->get( $parentid );

    $ids = json_decode( $ids, true );

    foreach ( $ids as $id ) {
        $Trash->restore( $id, $Folder );
    }
}

\QUI::$Ajax->register(
    'ajax_trash_media_restore',
    array('project', 'ids', 'parentid'),
    'Permission::checkAdminUser'
);
