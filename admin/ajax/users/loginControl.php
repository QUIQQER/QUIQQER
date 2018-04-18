<?php

/**
 * Return the login control
 *
 * @param array $authenticators - pre-defined list of authenticators [if ommitted use QUIQQER settings]
 */
QUI::$Ajax->registerFunction('ajax_users_loginControl', function ($authenticators = null) {
    if (empty($authenticators)) {
        $authenticators = [];
    } else {
        $authenticators = json_decode($authenticators, true);
    }

    $Login = new QUI\Users\Controls\Login([
        'authenticators' => $authenticators
    ]);

    $result = '';
    $result .= $Login->create();
    $result .= QUI\Control\Manager::getCSS();

    return $result;
}, [
    'authenticators'
]);
