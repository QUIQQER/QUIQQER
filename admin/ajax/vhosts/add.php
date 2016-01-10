<?php

/**
 * Add a new vhost and return the clean vhost
 *
 * @param string $vhost
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_vhosts_add',
    function ($vhost) {
        $VhostManager = new QUI\System\VhostManager();

        return $VhostManager->addVhost($vhost);
    },
    array('vhost'),
    'Permission::checkSU'
);
