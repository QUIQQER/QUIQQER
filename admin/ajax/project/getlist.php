<?php

/**
 * Return the project list
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_getlist',
    function () {
        return QUI\Projects\Manager::getConfig()->toArray();
    },
    false,
    'Permission::checkAdminUser'
);
