<?php

/**
 * Return the current quiqqer version
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_version',
    function () {
        return QUI::getPackageManager()->getVersion();
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
