<?php

/**
 * Return the package-independent locale data for a project title.
 *
 * @param string $project
 *
 * @return array<string, mixed>
 */

QUI::getAjax()->registerFunction(
    'ajax_project_get_titleLocaleData',
    static function (string $project): array {
        return QUI\Projects\Manager::getProject($project)->getTitleLocaleData();
    },
    ['project'],
    'Permission::checkAdminUser'
);
