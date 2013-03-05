<?php

/**
 * Nur subfolders bekommen
 *
 * @param String $project
 * @param String $lang
 * @param String $fileid
 *
 * @return Array
 */
function ajax_media_getsubfolders($project, $fileid)
{
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    $children  = array();
    $_children = $File->getSubFolders();

    // create children data
    foreach ( $_children as $Child ) {
        $children[] = Projects_Media_Utils::parseForMediaCenter( $Child );
    }

    return $children;
}
QUI::$Ajax->register('ajax_media_getsubfolders', array('project', 'fileid'), 'Permission::checkAdminUser');

?>