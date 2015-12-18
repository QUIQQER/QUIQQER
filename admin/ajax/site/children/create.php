<?php

/**
 * Creates a child
 *
 * @param string $project - Project name
 * @param integer $id - Parent ID
 * @param string $attributes - JSON Array, child attributes
 * @return array
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
