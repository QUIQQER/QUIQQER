<?php

/**
 * Get the available versions of quiqqer
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_getQuiqqerVersions',
    function () {
        return array(
            "1.0.0",
            "1.0.1",
            "1.0.2",
            "1.0.3",
            "1.0.4",
            "1.0.5",
            "1.0.6",
            "1.0.7",
            "1.0.8",
            "dev-dev",
            "dev-master"
        );
    },
    false,
    'Permission::checkUser'
);
