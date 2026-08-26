<?php

/**
 * Return the project list
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_project_getlist',
    static function (): array {
        return QUI\Projects\Manager::getConfig()->toArray();
    },
    false,
    'Permission::checkAdminUser'
);
