<?php

/**
 * Destroy files
 *
 * @param String $project - Name of the project
 * @param String $ids - JSON Array, List of IDs
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

\QUI::$Ajax->register(
    'ajax_trash_media_destroy',
    array('project', 'ids'),
    'Permission::checkAdminUser'
);
