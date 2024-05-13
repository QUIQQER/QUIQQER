<?php

/**
 * Clear a site name
 *
 * @param string $project
 * @param string $name
 *
 * @return string
 */

QUI::$Ajax->registerFunction(
    'ajax_site_clear',
    static function ($project, $name): string {
        return QUI\Projects\Site\Utils::clearUrl(
            $name,
            QUI::getProjectManager()->decode($project)
        );
    },
    ['project', 'name'],
    'Permission::checkAdminUser'
);
