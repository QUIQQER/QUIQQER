<?php

/**
 * Return the project list
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_project_getlist',
    fn() => QUI\Projects\Manager::getConfig()->toArray(),
    false,
    'Permission::checkAdminUser'
);
