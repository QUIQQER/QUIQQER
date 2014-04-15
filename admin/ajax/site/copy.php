<?php

/**
 * Copy a site
 *
 * @param String $project
 * @param String $lang
 * @param Integer $id
 * @param Integer $newParentId
 *
 * @return Integer - new site id
 */
function ajax_site_copy($project, $lang, $id, $newParentId)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $NewSite = $Site->copy( (int)$newParentId );

    return $NewSite->getId();
}

\QUI::$Ajax->register(
    'ajax_site_copy',
    array( 'project', 'lang', 'id', 'newParentId' ),
    'Permission::checkAdminUser'
);
