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

        $TabBar   = QUI\Projects\Sites::getTabs($Site);
        $children = $TabBar->getChildren();
        $result   = [];
        $names    = [];

        /* @var $Itm QUI\Controls\Toolbar\Tab */
        foreach ($children as $Itm) {
            $name = $Itm->getAttribute('name');

            // filter duplicate
            if (isset($names[$name])) {
                continue;
            }

            $result[]     = $Itm->getAttributes();
            $names[$name] = true;
        }

        return $result;
    },
    ['project', 'id'],
    'Permission::checkAdminUser'
);
