<?php

/**
 * Agree to package store terms of use and save it to the quiqqer system
 *
 * @return void
 */
QUI::$Ajax->registerFunction(
    'ajax_packages_agreeToTermsOfUse',
    function () {
        $Config = new QUI\Config(ETC_DIR . 'conf.ini.php');
        $Config->set('packagestore', 'agreedToTermsOfUse', 1);
        $Config->save();
    },
    array(),
    'Permission::checkAdminUser'
);
