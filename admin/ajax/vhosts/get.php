<?php

/**
 * Return the vhost data
 *
 * @param string $vhost - vhost
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_vhosts_get',
    static function ($vhost) {
        $VhostManager = new QUI\System\VhostManager();

        return $VhostManager->getVhost($vhost);
    },
    ['vhost'],
    'Permission::checkSU'
);
