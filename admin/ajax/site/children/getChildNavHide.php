<?php

QUI::$Ajax->registerFunction(
    'ajax_site_children_getChildNavHide',
    static function ($project, $siteId): int {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$siteId);

        return QUI\Utils\Site::getChildNaveHide($Site);
    },
    ['project', 'siteId']
);
