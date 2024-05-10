<?php

/**
 * Activate a site
 *
 * @param string $project
 * @param string $id
 * @return bool
 */

QUI::$Ajax->registerFunction(
    'ajax_site_activate',
    static function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);

        try {
            $Site->activate();
        } catch (QUI\Exception $Exception) {
            match ($Exception->getCode()) {
                1119, 1120 => QUI::getMessagesHandler()->addAttention(
                    $Exception->getMessage()
                ),
                default => throw $Exception,
            };
        }

        return $Site->getAttribute('active') ? 1 : 0;
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
