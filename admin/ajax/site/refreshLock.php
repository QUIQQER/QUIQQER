<?php

QUI::getAjax()->registerFunction(
    'ajax_site_refreshLock',
    static function ($project, $id, $token): bool {
        if (!is_string($token)) {
            throw new QUI\Exception('Invalid editing lock token.', 400);
        }

        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, $id);
        return $Site->refreshLock($token);
    },
    ['project', 'id', 'token'],
    'Permission::checkAdminUser'
);
