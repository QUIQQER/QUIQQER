<?php

/**
 * Return the site type title
 *
 * @param string $sitetype - name of the sitetype
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_project_types_get_title',
    function ($sitetype) {
        return QUI::getPackageManager()->getSiteTypeName($sitetype);
    },
    ['sitetype'],
    'Permission::checkAdminUser'
);
