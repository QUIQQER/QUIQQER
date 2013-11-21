<?php

/**
 * Media item roh daten bekommen
 *
 * @param String $project
 * @param String $parentid
 *
 * @return Array
 */
function ajax_media_rename($project, $id, $newname)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $Item    = $Media->get( $id );

    $Item->rename( $newname );

    return $Item->getAttribute( 'name' );
}

\QUI::$Ajax->register(
    'ajax_media_rename',
    array('project', 'id', 'newname'),
    'Permission::checkAdminUser'
);
