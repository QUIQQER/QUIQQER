<?php

/**
 * Add a new language link
 *
 * @param string $project
 * @param string $id
 * @param string $linkedParams - JSON Array
 */

QUI::$Ajax->registerFunction(
    'ajax_site_language_add',
    static function ($project, $id, $linkedParams) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        $linkedParams = \json_decode($linkedParams, true);

        $Site->addLanguageLink($linkedParams['lang'], (int)$linkedParams['id']);
    },
    ['project', 'id', 'linkedParams'],
    'Permission::checkAdminUser'
);
