<?php

/**
 * Checks if terms of use for the backend package store have been accepted
 *
 * @return boolean
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_packages_getStoreUrl',
    function () {
        $Config = new QUI\Config(ETC_DIR . 'conf.ini.php');
        return $Config->get('packagestore', 'url');
    },
    array(),
    'Permission::checkAdminUser'
);
