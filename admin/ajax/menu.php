<?php

/**
 * This file includes ajax_menu
 */

/**
 * Return the administration menu
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'ajax_menu',
    static function (): array {
        $Menu = new QUI\Workspace\Menu();

        return $Menu->getMenu();
    },
    false,
    'Permission::checkAdminUser'
);
