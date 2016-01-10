<?php

/**
 * Get the changelog from http://update.quiqqer.com/CHANGELOG
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_changelog',
    function () {
        return QUI\Utils\Request\Url::get(
            'http://update.quiqqer.com/CHANGELOG'
        );
    },
    false,
    'Permission::checkUser'
);
