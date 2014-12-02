<?php

/**
 * Return the tab content
 *
 * @param String $project
 * @param String $id
 * @param String $tab
 * @return String
 */
function ajax_site_categories_template($project, $id, $tab)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    return \QUI\Utils\String::removeLineBreaks(
        \QUI\Utils\DOM::getTabHTML( $tab, $Site )
    );
}

\QUI::$Ajax->register(
    'ajax_site_categories_template',
    array('project', 'id', 'tab'),
    'Permission::checkAdminUser'
);
