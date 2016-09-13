<?php

/**
 * Saves a site
 *
 * @param string $project - project data
 * @param integer $id - Site ID
 * @param string $attributes - JSON Array
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_save',
    function ($project, $id, $attributes) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $attributes = json_decode($attributes, true);

        $Site->setAttributes($attributes);
        $Site->save();
        $Site->refresh();

        require_once 'get.php';

        $result = QUI::$Ajax->callRequestFunction('ajax_site_get', array(
            'project' => json_encode($Project->toArray()),
            'id'      => $id
        ));

        return $result['result'];
    },
    array('project', 'id', 'attributes'),
    'Permission::checkAdminUser'
);
