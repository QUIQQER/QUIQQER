<?php

/**
 * Return the available toolbars
 *
 * @param string / Integer $uid
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_editor_get_toolbars',
    static function (): array {
        return QUI\Editor\Manager::getToolbars();
    },
    false,
    'Permission::checkSU'
);
