<?php

/**
 * Add a new vhost and return the clean vhost
 *
 * @param string $vhost
 * @return string
 */
function ajax_vhosts_add($vhost)
{
    $VhostManager = new QUI\System\VhostManager();

    return $VhostManager->addVhost($vhost, array());
}

QUI::$Ajax->register(
    'ajax_vhosts_add',
    array('vhost'),
    'Permission::checkSU'
);
