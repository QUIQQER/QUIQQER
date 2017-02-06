<?php

/**
 *
 */
QUI::$Ajax->registerFunction(
    'ajax_users_login',
    function ($authenticator, $params, $globalauth) {
        QUI::getUsers()->authenticate(
            $authenticator,
            json_decode($params, true)
        );

        if ($globalauth) {
            QUI::getSession()->set('auth-globals', 1);
        }

        $Login = new QUI\Users\Controls\Login();
        $next  = $Login->next();

        if (empty($next)) {
            QUI::getUsers()->login();
        }

        $control = '';
        $control .= $Login->create();
        $control .= QUI\Control\Manager::getCSS();

        return array(
            'authenticator' => $Login->next(),
            'control'       => $control
        );
    },
    array('authenticator', 'params', 'globalauth')
);
