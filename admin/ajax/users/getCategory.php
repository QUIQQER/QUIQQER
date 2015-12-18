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
function ajax_users_getCategory($uid, $plugin, $tab)
{
    return QUI\Users\Utils::getTab($uid, $plugin, $tab);
}

QUI::$Ajax->register(
    'ajax_users_getCategory',
    array('uid', 'plugin', 'tab'),
    'Permission::checkSU'
);
