<?php

/**
 * Tabs bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return Array
 */
function ajax_site_categories_get($project, $lang, $id)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Tabbar   = \QUI\Projects\Sites::getTabs( $Site );
    $children = $Tabbar->getChildren();

    $result = array();

    foreach ( $children as $Itm ) {
        $result[] = $Itm->getAttributes();
    }

    return $result;
}

\QUI::$Ajax->register(
    'ajax_site_categories_get',
    array('project', 'lang', 'id'),
    'Permission::checkAdminUser'
);
