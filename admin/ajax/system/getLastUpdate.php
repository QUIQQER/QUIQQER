<?php

/**
 * Return the date of the last update
 *
 * @param string|bool $formatted
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_system_getLastUpdate',
    function ($formatted) {
        $date = QUI::getPackageManager()->getLastUpdateDate();

        if (!isset($formatted) || !$formatted) {
            return $date;
        }

        return QUI::getLocale()->formatDate($date, '%B %d %Y, %X %Z');
    },
    ['formatted'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
