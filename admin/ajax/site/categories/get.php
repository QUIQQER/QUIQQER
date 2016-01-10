<?php

/**
 * Return the tabs / categories
 *
 * @param string $project
 * @param string $id
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_categories_get',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Tabbar   = QUI\Projects\Sites::getTabs($Site);
        $children = $Tabbar->getChildren();

        $result = array();

        foreach ($children as $Itm) {
            /* @var $Itm QUI\Controls\Toolbar\Tab */
            $result[] = $Itm->getAttributes();
        }

        return $result;
    },
    array('project', 'id'),
    'Permission::checkAdminUser'
);
