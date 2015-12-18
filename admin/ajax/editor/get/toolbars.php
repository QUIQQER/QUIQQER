<?php

/**
 * Return the available toolbars
 *
 * @param string / Integer $uid
 *
 * @return array
 */
function ajax_editor_get_toolbars()
{
    return QUI\Editor\Manager::getToolbars();
}

QUI::$Ajax->register('ajax_editor_get_toolbars', false, 'Permission::checkSU');
