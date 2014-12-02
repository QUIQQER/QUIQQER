<?php

/**
 * Clear a site name
 *
 * @param String $project
 * @param String $name
 *
 * @return String
 */
function ajax_site_clear($project, $name)
{
    return \QUI\Projects\Site\Utils::clearUrl(
        $name,
        \QUI::getProjectManager()->decode( $project )
    );
}

\QUI::$Ajax->register(
    'ajax_site_clear',
    array( 'project', 'name' ),
    'Permission::checkAdminUser'
);
