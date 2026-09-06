<?php

/** Search selectable pages in one project language, including collapsed sitemap branches. */

QUI::getAjax()->registerFunction(
    'ajax_project_sites_searchForSelection',
    static function ($project, $search): array {
        if (!is_string($search) || mb_strlen($search) > 200) {
            throw new QUI\Exception('Invalid site search.', 400);
        }

        $search = trim($search);

        if ($search === '') {
            return ['items' => [], 'limited' => false];
        }

        $Project = QUI::getProjectManager()->decode($project);
        $sites = $Project->search($search, ['name', 'title']);
        $limited = count($sites) >= 50;

        if (ctype_digit($search)) {
            try {
                array_unshift($sites, $Project->get((int)$search));
            } catch (QUI\Exception) {
                // A numeric term may match a title without being an existing page ID.
            }
        }

        $items = [];

        foreach ($sites as $Site) {
            if ($Site->getAttribute('deleted') || !$Site->hasPermission('quiqqer.projects.site.view')) {
                continue;
            }

            $items[$Site->getId()] = [
                'id' => $Site->getId(),
                'name' => $Site->getAttribute('name'),
                'title' => $Site->getAttribute('title')
            ];
        }

        return ['items' => array_values($items), 'limited' => $limited];
    },
    ['project', 'search'],
    'Permission::checkAdminUser'
);
