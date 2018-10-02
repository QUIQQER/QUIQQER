<?php

/**
 * Get the available versions of quiqqer
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_getQuiqqerVersions',
    function () {
        return [
            "1.*",
            "dev-master",
            "dev-dev"
        ];
    },
    false,
    'Permission::checkUser'
);
