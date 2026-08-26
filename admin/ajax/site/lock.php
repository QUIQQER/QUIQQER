<?php

/**
 * Lock a site
 *
 * @param string $project - Project data; JSON Array
 * @param string $id - Site ID
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_site_lock',
    static function ($project, $id): void {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, $id);

        $Site->lock();
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
