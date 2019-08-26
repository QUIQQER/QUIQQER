<?php

/**
 * Lock a site
 *
 * @param string $project - Project data; JSON Array
 * @param string $id - Site ID
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_unlock',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, $id);

        $Site->unlockWithRights();
    },
    array('project', 'id'),
    'Permission::checkAdminUser'
);
