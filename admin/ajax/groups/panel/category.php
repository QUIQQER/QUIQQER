<?php

/**
 * Return the content of a panel category
 *
 * @param integer $gid
 * @param string $plugin
 * @param string $tab
 *
 * @return string
 */
function ajax_groups_panel_category($gid, $plugin, $tab)
{
    return \QUI\Groups\Utils::getTab( $gid, $plugin, $tab );
}

QUI::$Ajax->register(
    'ajax_groups_panel_category',
    array('gid', 'plugin', 'tab'),
    'Permission::checkSU'
);
