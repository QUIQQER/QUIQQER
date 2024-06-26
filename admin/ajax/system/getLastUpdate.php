<?php

/**
 * Return the date of the last update
 *
 * @param string|bool $formatted
 * @return string
 */

QUI::$Ajax->registerFunction(
    'ajax_system_getLastUpdate',
    static function ($formatted) {
        $date = QUI::getPackageManager()->getLastUpdateDate();

        if (!isset($formatted) || !$formatted) {
            return $date;
        }

        return QUI::getLocale()->formatDate($date, 'MMMM dd yyyy, HH:mm:ss z');
    },
    ['formatted'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
