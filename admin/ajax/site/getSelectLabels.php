<?php

/**
 * Return display data for site select values
 *
 * @param string $project
 * @param string $selectors - JSON array
 *
 * @return array<string, array<string, string>>
 */

QUI::getAjax()->registerFunction(
    'ajax_site_getSelectLabels',
    static function ($project, $selectors): array {
        $Project = QUI::getProjectManager()->decode($project);

        return QUI\Projects\Site\SelectLabelResolver::resolveEncoded($Project, $selectors);
    },
    ['project', 'selectors'],
    'Permission::checkAdminUser'
);
