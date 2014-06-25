<?php

/**
 * Tab einer Benutzereinstellung bekommen
 *
 * @param unknown_type $uid
 * @param unknown_type $plugin
 * @param unknown_type $tab
 *
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
