<?php

/**
 * This file includes ajax_menu
 */

/**
 * Return the administration menu
 *
 * @return array
 */
function ajax_menu()
{
    $Menu = new QUI\Workspace\Menu();

    return $Menu->getMenu();
}

QUI::$Ajax->register(
    'ajax_menu',
    false,
    'Permission::checkAdminUser'
);
