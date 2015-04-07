<?php

/**
 * Return the site data
 *
 * @param String $project
 * @param String $id
 *
 * @return Array
 */
function ajax_site_get($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $attributes = $Site->getAttributes();

    $attributes['icon'] = \QUI::getPluginManager()->getIconByType(
        $Site->getAttribute('type')
    );

    return array(
        'modules'      => \QUI\Projects\Site\Utils::getAdminSiteModulesFromSite( $Site ),
        'attributes'   => $attributes,
        'has_children' => $Site->hasChildren(),
        'parentid'     => $Site->getParentId(),
        'url'          => URL_DIR . $Site->getUrlRewrited()
    );
}

\QUI::$Ajax->register(
    'ajax_site_get',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
