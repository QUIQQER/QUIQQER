<?php

/**
 * Return the tab content
 *
 * @param string $project
 * @param string $id
 * @param string $tab
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_site_categories_template',
    function ($project, $id, $tab) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        return QUI\Utils\StringHelper::removeLineBreaks(
            QUI\Utils\DOM::getTabHTML($tab, $Site)
        );
    },
    array('project', 'id', 'tab'),
    'Permission::checkAdminUser'
);
