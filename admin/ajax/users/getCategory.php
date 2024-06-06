<?php

/**
 * Tab einer Benutzereinstellung bekommen
 *
 * @param integer $uid
 * @param string $plugin
 * @param string $tab
 *
 * @return string
 */

QUI::$Ajax->registerFunction(
    'ajax_users_getCategory',
    static function ($uid, $plugin, $tab): string {
        return QUI\Users\Utils::getTab($uid, $plugin, $tab);
    },
    ['uid', 'plugin', 'tab'],
    'Permission::checkAdminUser'
);
