<?php

/**
 * Return the project list
 *
 * @return array
 */
function ajax_project_getlist()
{
    return QUI\Projects\Manager::getConfig()->toArray();
}

QUI::$Ajax->register(
    'ajax_project_getlist',
    false,
    'Permission::checkAdminUser'
);
