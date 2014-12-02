<?php

/**
 * Return the project sitemap
 *
 * @param String $project - JSON Array; Project data
 * @return string
 */
function ajax_project_html_sitemap($project)
{
    $Project  = \QUI::getProjectManager()->decode( $project );
    $Template = \QUI::getTemplateManager()->getEngine();

    $Template->assign(array(
        'Project' => $Project,
        'Site'    => $Project->firstChild()
    ));

    return $Template->fetch(
        LIB_DIR .'templates/sitemap.html'
    );
}

\QUI\Ajax::register('ajax_project_html_sitemap', array('project'));
