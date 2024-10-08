<?php

/**
 * Return the action buttons from the site
 *
 * @param string $id
 * @param string $project
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_site_buttons_get',
    static function ($project, $id): array {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        return QUI\Projects\Sites::getButtons($Site)->toArray();
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
