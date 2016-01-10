<?php

/**
 * Activate a site
 *
 * @param string $project
 * @param string $id
 * @return bool
 */
QUI::$Ajax->registerFunction(
    'ajax_site_activate',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->activate();

        return $Site->getAttribute('active') ? 1 : 0;
    },
    array('project', 'id'),
    'Permission::checkAdminUser'
);
