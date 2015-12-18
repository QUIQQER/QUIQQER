<?php

/**
 * Return the vhost data
 *
 * @param string $vhost - vhost
 * @return array
 */
function ajax_vhosts_get($vhost)
{
    $VhostManager = new QUI\System\VhostManager();

    return $VhostManager->getVhost($vhost);
}

QUI::$Ajax->register(
    'ajax_vhosts_get',
    array('vhost'),
    'Permission::checkSU'
);
