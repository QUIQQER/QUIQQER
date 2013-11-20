<?php

/**
 * Seiten wiederherstellen
 *
 * @param String $project
 * @param String $lang
 * @param JSON Array $ids
 */
function ajax_trash_media_restore($project, $ids, $parentid)
{
    $Project = \QUI::getProject($project);
    $Media   = $Project->getMedia();
    $Trash   = $Media->getTrash();
    $Folder  = $Media->get( $parentid );

    $ids = json_decode($ids, true);

    foreach ( $ids as $id ) {
        $Trash->restore( $id, $Folder );
    }
}

QUI::$Ajax->register(
	'ajax_trash_media_restore',
    array('project', 'ids', 'parentid'),
    'Permission::checkAdminUser'
);

?>