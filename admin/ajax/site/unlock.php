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
    static function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, $id);

        $Site->unlockWithRights();
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
