<?php

/**
 * Return the login control
 */
QUI::$Ajax->registerFunction('ajax_users_loginControl', function () {
    $Login = new QUI\Users\Controls\Login();

    $result = '';
    $result .= $Login->create();
    $result .= QUI\Control\Manager::getCSS();

    return $result;
});
