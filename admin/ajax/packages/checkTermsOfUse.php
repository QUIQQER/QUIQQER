<?php

/**
 * Checks if terms of use for the backend package store have been accepted
 *
 * @return boolean
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_packages_checkTermsOfUse',
    function () {
        $Config    = new QUI\Config(ETC_DIR . 'conf.ini.php');
        $agreement = $Config->get('packagestore', 'agreedToTermsOfUse');

        return !empty($agreement);
    },
    array(),
    'Permission::checkAdminUser'
);
