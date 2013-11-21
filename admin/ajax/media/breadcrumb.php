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
function ajax_media_breadcrumb($project, $fileid)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    $parents    = $File->getParents();
    $breadcrumb = array();

    // create breadcrumb data
    foreach ($parents as $Parent) {
        $breadcrumb[] = \QUI\Projects\Media\Utils::parseForMediaCenter( $Parent );
    }

    $breadcrumb[] = \QUI\Projects\Media\Utils::parseForMediaCenter( $File );

    return $breadcrumb;
}

\QUI::$Ajax->register(
    'ajax_media_breadcrumb',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
