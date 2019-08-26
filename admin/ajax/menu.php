<?php

/**
 * This file includes ajax_menu
 */

/**
 * Return the administration menu
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_menu',
    function () {
        $Menu = new QUI\Workspace\Menu();
        return $Menu->getMenu();
    },
    false,
    'Permission::checkAdminUser'
);
