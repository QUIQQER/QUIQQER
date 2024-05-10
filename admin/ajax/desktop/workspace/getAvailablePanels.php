<?php

/**
 * Return all available panels
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_getAvailablePanels',
    static fn() => QUI\Workspace\Manager::getAvailablePanels(),
    false,
    'Permission::checkUser'
);
