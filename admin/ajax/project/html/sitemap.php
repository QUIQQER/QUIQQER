<?php

/**
 *
 * @param unknown $project
 * @param unknown $lang
 * @return string
 */
function ajax_project_html_sitemap($project, $lang)
{
    $Project  = \QUI\Projects\Manager::getProject( $project, $lang );
    $Template = \QUI::getTemplateManager()->getEngine();

    $Template->assign(array(
        'Project' => $Project,
        'Site'    => $Project->firstChild()
    ));

    return $Template->fetch(
        LIB_DIR .'templates/sitemap.html'
    );
}

\QUI\Ajax::register('ajax_project_html_sitemap', array('project', 'lang'));
