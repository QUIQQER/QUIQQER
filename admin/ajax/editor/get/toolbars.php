<?php

/**
 * Return the available toolbars
 *
 * @param string / Integer $uid
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_editor_get_toolbars',
    fn() => QUI\Editor\Manager::getToolbars(),
    false,
    'Permission::checkSU'
);
