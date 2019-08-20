<?php

/**
 * Return the site data
 *
 * @param string $project
 * @param string $id
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_get',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $attributes         = $Site->getAttributes();
        $attributes['icon'] = QUI::getPackageManager()->getIconBySiteType($Site->getAttribute('type'));

        return [
            'modules'      => QUI\Projects\Site\Utils::getAdminSiteModulesFromSite($Site),
            'attributes'   => $attributes,
            'has_children' => $Site->hasChildren(true),
            'parentid'     => $Site->getParentId(),
            'url'          => $Site->getUrlRewritten(),
            'hostUrl'      => $Site->getUrlRewrittenWithHost()
        ];
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
