<?php

/**
 * Remove a language link
 *
 * @param string $project
 * @param string $id
 * @param string $linkedParams - JSON Array
 */

QUI::$Ajax->registerFunction(
    'ajax_site_language_remove',
    static function ($project, $id, $linkedParams) {
        $linkedParams = json_decode($linkedParams, true);

        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->removeLanguageLink($linkedParams['lang']);
    },
    ['project', 'id', 'linkedParams'],
    'Permission::checkAdminUser'
);
