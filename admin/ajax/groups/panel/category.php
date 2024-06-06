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

QUI::$Ajax->registerFunction(
    'ajax_groups_panel_category',
    static function ($gid, $plugin, $tab): string {
        return QUI\Groups\Utils::getTab($gid, $plugin, $tab);
    },
    ['gid', 'plugin', 'tab'],
    'Permission::checkSU'
);
