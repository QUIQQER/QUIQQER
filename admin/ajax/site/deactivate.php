<?php

/**
 * Deactivate a site
 *
 * @param string $project
 * @param string $id
 * @return bool
 */
QUI::$Ajax->registerFunction(
    'ajax_site_deactivate',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->deactivate();

        return !$Site->getAttribute('active') ? 0 : 1;
    },
    array('project', 'id'),
    'Permission::checkAdminUser'
);
