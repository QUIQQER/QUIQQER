<?php

/**
 * Return the tab content
 *
 * @param string $project
 * @param string $id
 * @param string $tab
 * @return string
 */
function ajax_site_categories_template($project, $id, $tab)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

    return QUI\Utils\StringHelper::removeLineBreaks(
        QUI\Utils\DOM::getTabHTML($tab, $Site)
    );
}

QUI::$Ajax->register(
    'ajax_site_categories_template',
    array('project', 'id', 'tab'),
    'Permission::checkAdminUser'
);
