<?php

/**
 * Return the first child of a project
 *
 * @param string $project - Project Data, JSON Array
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_firstchild',
    function ($project) {
        $Project = QUI::getProjectManager()->decode($project);
        $First   = $Project->firstChild();
        $Temp    = new QUI\Projects\Site\Edit($Project, $First->getId());

        $result                 = $Temp->getAttributes();
        $result['has_children'] = 1;

        return $result;
    },
    array('project', 'lang'),
    'Permission::checkAdminUser'
);
