<?php

/**
 * Return the project settings template
 *
 * @param String $project - JSON Project Data
 * @return String
 */
function ajax_project_panel_settings($project)
{
    $Engine  = QUI::getTemplateManager()->getEngine(true);
    $Project = QUI::getProjectManager()->decode($project);

    $Engine->assign(array(
        'QUI'     => new \QUI(),
        'Project' => $Project
    ));

    return $Engine->fetch(SYS_DIR . 'template/project/settings.html');
}

QUI::$Ajax->register(
    'ajax_project_panel_settings',
    array('project'),
    'Permission::checkAdminUser'
);
