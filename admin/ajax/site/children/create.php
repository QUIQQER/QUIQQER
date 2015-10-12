<?php

/**
 * Creates a child
 *
 * @param String $project - Project name
 * @param Integer $id - Parent ID
 * @param String $attributes - JSON Array, child attributes
 * @return Array
 */
function ajax_site_children_create($project, $id, $attributes)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

    $childid = $Site->createChild(
        json_decode($attributes, true)
    );

    $Child = new QUI\Projects\Site\Edit($Project, $childid);

    return $Child->getAttributes();
}

QUI::$Ajax->register(
    'ajax_site_children_create',
    array('project', 'id', 'attributes')
);
