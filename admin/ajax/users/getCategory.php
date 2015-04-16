<?php

/**
 * Tab einer Benutzereinstellung bekommen
 *
 * @param Integer $uid
 * @param String $plugin
 * @param String $tab
 * @return String
 */
function ajax_users_getCategory($uid, $plugin, $tab)
{
    return \QUI\Users\Utils::getTab( $uid, $plugin, $tab );
}

\QUI::$Ajax->register(
    'ajax_users_getCategory',
    array('uid', 'plugin', 'tab'),
    'Permission::checkSU'
);
