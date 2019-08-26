<?php

/**
 * Return the project sitemap
 *
 * @param string $project - JSON Array; Project data
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_project_html_sitemap',
    function ($project) {
        $Project  = QUI::getProjectManager()->decode($project);
        $Template = QUI::getTemplateManager()->getEngine();

        $Template->assign([
            'Project' => $Project,
            'Site'    => $Project->firstChild()
        ]);

        return $Template->fetch(
            LIB_DIR.'templates/sitemap.html'
        );
    },
    ['project']
);
