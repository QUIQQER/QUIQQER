<?php

/**
 * Return the parent ids
 *
 * @param string $project
 * @param string $id
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_site_path',
    static function ($project, $id): array {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        $pids = [];
        $parents = $Site->getParents();

        foreach ($parents as $Parent) {
            /* @var $Parent QUI\Projects\Site */
            $pids[] = $Parent->getId();
        }

        return $pids;
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
