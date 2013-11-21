<?php

/**
 * Erste Seite vom Projekt bekommen
 *
 * @return Array
 */
function ajax_project_firstchild($project, $lang)
{
    $Project = \QUI::getProject($project, $lang);
    $First   = $Project->firstChild();
    $Temp    = new \QUI\Projects\Site\Edit($Project, $First->getId());

    $result = $Temp->getAllAttributes();
    $result['has_children'] = 1;

    return $result;
}

\QUI::$Ajax->register(
    'ajax_project_firstchild',
    array('project', 'lang'),
    'Permission::checkAdminUser'
);
