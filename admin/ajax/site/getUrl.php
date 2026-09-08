<?php

/**
 * Return a site's URL and parent without reloading editor attributes.
 */

QUI::getAjax()->registerFunction(
    'ajax_site_getUrl',
    static function ($project, $id): array {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);
        $Site->checkPermission('quiqqer.projects.site.view');

        return [
            'url' => $Site->getUrlRewritten(),
            'parentid' => $Site->getParentId()
        ];
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
