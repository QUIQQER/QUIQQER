<?php

/**
 * Return the content of a panel category
 *
 * @param unknown_type $uid
 * @param unknown_type $plugin
 * @param unknown_type $tab
 *
 * @return String
 */
function ajax_groups_panel_category($gid, $plugin, $tab)
{
    return Groups_Utils::getTab( $gid, $plugin, $tab );
}

QUI::$Ajax->register(
	'ajax_groups_panel_category',
    array('gid', 'plugin', 'tab'),
    'Permission::checkSU'
);

?>