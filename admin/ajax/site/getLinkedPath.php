<?php

/**
 * Create a linkage / shortcut
 *
 * @param string $project
 * @param integer $id
 * @param integer $newParentId
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_getLinkedPath',
    function ($project, $id, $parentId) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);
        $Parent  = new QUI\Projects\Site\Edit($Project, (int)$parentId);

        $parentIds = $Parent->getParentIdTree();
        $path      = '/';

        foreach ($parentIds as $id) {
            $ParentSite = new QUI\Projects\Site\Edit($Project, (int)$id);
            $path .= $ParentSite->getAttribute('name') . '/';
        }

        $path .= $Parent->getAttribute('name') . '/';
        $path .= $Site->getAttribute('name');

        return $path;
    },
    array('project', 'id', 'parentId'),
    'Permission::checkAdminUser'
);
