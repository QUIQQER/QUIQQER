<?php

/**
 *
 */
QUI::$Ajax->registerFunction(
    'ajax_site_children_getChildType',
    function ($project, $siteId) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$siteId);

        return QUI\Utils\Site::getChildType($Site);
    },
    ['project', 'siteId']
);
