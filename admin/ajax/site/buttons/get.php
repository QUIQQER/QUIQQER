<?php

/**
 * Return the action buttons from the site
 *
 * @param string $id
 * @param string $project
 *
 * @return array
 */
function ajax_site_buttons_get($project, $id)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

    return QUI\Projects\Sites::getButtons($Site)->toArray();
}

QUI::$Ajax->register(
    'ajax_site_buttons_get',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
