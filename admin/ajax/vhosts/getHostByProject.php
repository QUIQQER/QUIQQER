<?php

/**
 * Return the vhost data
 *
 * @param string $project - name of the project
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_vhosts_getHostByProject',
    function ($project) {
        $Project = QUI::getProjectManager()->decode($project);
        $Manager = new QUI\System\VhostManager();

        return $Manager->getHostByProject($Project->getName(), $Project->getLang());
    },
    array('project'),
    'Permission::checkSU'
);
