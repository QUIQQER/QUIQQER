<?php

/**
 * Return the date of the last update check
 *
 * @param string|bool $formatted
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_system_getLastUpdateCheck',
    function ($formatted) {
        $date = QUI::getPackageManager()->getLastUpdateCheckDate();

        if (!isset($formatted) || !$formatted) {
            return $date;
        }

        return QUI::getLocale()->formatDate($date, '%B %d %Y, %X %Z');
    },
    ['formatted'],
    'Permission::checkSU'
);
