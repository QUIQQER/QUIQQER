<?php

/**
 * Get the elements in the media trash
 *
 * @param String $project - Project data, JSON Array
 * @param String $params
 * @return Array
 */
function ajax_trash_media($project, $params)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Media   = $Project->getMedia();
    $Trash   = $Media->getTrash();

    return $Trash->getList(
        json_decode( $params, true )
    );
}

\QUI::$Ajax->register(
    'ajax_trash_media',
    array('project', 'params'),
    'Permission::checkAdminUser'
);
