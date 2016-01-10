<?php

/**
 * Copy a site
 *
 * @param string $project
 * @param integer $id
 * @param string $newParent - JSON Data
 *
 * @return integer - new site id
 */
QUI::$Ajax->registerFunction(
    'ajax_site_copy',
    function ($project, $id, $newParent) {
        $Project   = QUI::getProjectManager()->decode($project);
        $Site      = new QUI\Projects\Site\Edit($Project, (int)$id);
        $newParent = json_decode($newParent, true);

        if (is_numeric($newParent)) {
            $ParentProject = $Project;
            $newParentId   = $newParent;

        } else {
            $ParentProject = QUI::getProjectManager()->decode(
                $newParent['project']
            );

            $newParentId = $newParent['parentId'];
        }

        $NewSite = $Site->copy((int)$newParentId, $ParentProject);

        return $NewSite->getId();
    },
    array('project', 'id', 'newParent'),
    'Permission::checkAdminUser'
);
