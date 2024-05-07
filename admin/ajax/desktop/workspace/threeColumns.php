<?php

/**
 * Return three column workspace default
 *
 * @return string
 */

QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_threeColumns',
    fn() => QUI\Workspace\Manager::getThreeColumnDefault(),
    false,
    'Permission::checkUser'
);
