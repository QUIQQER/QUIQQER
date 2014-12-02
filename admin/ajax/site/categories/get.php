<?php

/**
 * Return the tabs / categories
 *
 * @param String $project
 * @param String $id
 * @return Array
 */
function ajax_site_categories_get($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
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
    array('project', 'id'),
    'Permission::checkAdminUser'
);
