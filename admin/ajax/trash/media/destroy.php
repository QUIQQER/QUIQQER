<?php

/**
 * Seiten zerstören
 *
 * @param String $project
 * @param String $lang
 * @param JSON Array $ids
 */
function ajax_trash_media_destroy($project, $ids)
{
    $Project = \QUI::getProject($project);
    $Media   = $Project->getMedia();
    $Trash   = $Media->getTrash();

    $ids = json_decode($ids, true);

    foreach ( $ids as $id ) {
        $Trash->destroy( $id );
    }
}

QUI::$Ajax->register(
	'ajax_trash_media_destroy',
    array('project', 'ids'),
    'Permission::checkAdminUser'
);

?>