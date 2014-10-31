<?php

/**
 * Clear a site name
 *
 * @param String $project
 * @param String $lang
 * @param Integer $newParentId
 *
 * @return String
 */
function ajax_site_clear($project, $lang, $name)
{
    return \QUI\Projects\Site\Utils::clearUrl(
        $name,
        \QUI::getProject( $project, $lang )
    );
}

\QUI::$Ajax->register(
    'ajax_site_clear',
    array( 'project', 'lang', 'name' ),
    'Permission::checkAdminUser'
);
