<?php

/**
 * Tab Inhalt bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return String
 */
function ajax_site_categories_template($project, $lang, $id, $tab)
{
    $Project = QUI::getProject( $project, $lang );
    $Site    = new Projects_Site_Edit( $Project, (int)$id );

    return \QUI\Utils\String::removeLineBreaks(
        Utils_Dom::getTabHTML( $tab, $Site )
    );
}

QUI::$Ajax->register(
    'ajax_site_categories_template',
    array('project', 'lang', 'id', 'tab'),
    'Permission::checkAdminUser'
);
