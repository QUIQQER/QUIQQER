<?php

/**
 * Add a new vhost and return the clean vhost
 *
 * @param string $vhost
 * @return string
 */

QUI::$Ajax->registerFunction(
    'ajax_vhosts_add',
    static function ($vhost): string {
        $VhostManager = new QUI\System\VhostManager();

        return $VhostManager->addVhost($vhost);
    },
    ['vhost'],
    'Permission::checkSU'
);
