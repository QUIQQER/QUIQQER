<?php

/**
 * Return the project settings template
 *
 * @param string $project - JSON Project Data
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_project_panel_settings',
    function ($project) {
        $Engine  = QUI::getTemplateManager()->getEngine(true);
        $Project = QUI::getProjectManager()->decode($project);

        $Engine->assign([
            'QUI'     => new QUI(),
            'Project' => $Project
        ]);

        return $Engine->fetch(SYS_DIR.'template/project/settings.html');
    },
    ['project'],
    'Permission::checkAdminUser'
);
