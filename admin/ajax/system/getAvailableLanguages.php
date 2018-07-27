<?php

/**
 * Get the available versions of quiqqer
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_getAvailableLanguages',
    function () {
        return QUI::availableLanguages();
    },
    false,
    'Permission::checkUser'
);
