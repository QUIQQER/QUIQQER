<?php

/**
 * Lock a site
 *
 * @param string $project - Project data; JSON Array
 * @param string $id - Site ID
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_site_unlock',
    static function ($project, $id, $token, $force): void {
        if (!is_string($token)) {
            throw new QUI\Exception('Invalid editing lock token.', 400);
        }

        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, $id);

        if ($force === true || $force === '1' || $force === 1) {
            $Site->unlockWithRights();
        } else {
            $Site->releaseEditingLock($token);
        }
    },
    ['project', 'id', 'token', 'force'],
    'Permission::checkAdminUser'
);
