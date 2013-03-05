<?php

/**
 * ID daten bekommen
 *
 * @param String $project
 * @param String $lang
 * @param String $fileid
 *
 * @return Array
 */
function ajax_media_get($project, $fileid)
{
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    $parents    = $File->getParents();
    $breadcrumb = array();
    $children   = array();
    $_children  = array();

    if ($File->getType() === 'Projects_Media_Folder') {
        $_children = $File->getChildren();
    }


    // create breadcrumb data
    foreach ($parents as $Parent) {
        $breadcrumb[] = Projects_Media_Utils::parseForMediaCenter( $Parent );
    }

    $breadcrumb[] = Projects_Media_Utils::parseForMediaCenter( $File );

    // create children data
    foreach ($_children as $Child) {
        $children[] = Projects_Media_Utils::parseForMediaCenter( $Child );
    }


    return array(
        'file'       => Projects_Media_Utils::parseForMediaCenter( $File ),
        'breadcrumb' => $breadcrumb,
        'children'   => $children
    );
}
QUI::$Ajax->register('ajax_media_get', array('project', 'fileid'), 'Permission::checkAdminUser');

?>