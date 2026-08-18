<?php

/**
 * Return the vhost data
 *
 * @param string $project - name of the project
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_vhosts_getHostByProject',
    static function ($project): string {
        $Project = QUI::getProjectManager()->decode($project);
        $Manager = new QUI\System\VhostManager();

        return $Manager->getHostByProject($Project->getName(), $Project->getLang());
    },
    ['project'],
    'Permission::checkSU'
);
