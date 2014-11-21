<?php

/**
 * Return the vhost data
 *
 * @param String $vhost - vhost
 * @return Array
 */
function ajax_vhosts_getHostByProject($project)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Manager = new \QUI\System\VhostManager();

    return $Manager->getHostByProject( $Project->getName(), $Project->getLang() );
}

\QUI::$Ajax->register(
    'ajax_vhosts_getHostByProject',
    array( 'project' ),
    'Permission::checkSU'
);
