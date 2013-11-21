<?php

/**
 * return the action buttons from the site
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return Array
 */
function ajax_site_buttons_get($project, $lang, $id)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    return \QUI\Projects\Sites::getButtons( $Site )->toArray();
}

\QUI::$Ajax->register(
    'ajax_site_buttons_get',
    array( 'project', 'lang', 'id' ),
    'Permission::checkAdminUser'
);
