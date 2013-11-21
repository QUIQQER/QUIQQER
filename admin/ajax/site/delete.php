<?php

/**
 * Delete a site
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 */
function ajax_site_delete($project, $lang, $id)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new \QUI\Projects\Site\Edit($Project, (int)$id);

    return $Site->delete();
}

\QUI::$Ajax->register(
    'ajax_site_delete',
    array('project', 'lang', 'id'),
    'Permission::checkAdminUser'
);
