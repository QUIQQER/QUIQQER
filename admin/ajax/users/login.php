<?php

/**
 *
 */
QUI::$Ajax->registerFunction(
    'ajax_users_login',
    function ($authenticator, $params) {
        QUI::getUsers()->authenticate(
            $authenticator,
            json_decode($params, true)
        );

        $Login = new QUI\Users\Controls\Login();
        $next  = $Login->next();

        if (empty($next)) {
            QUI::getUsers()->login();
        }

        return array(
            'authenticator' => $Login->next(),
            'control'       => $Login->create()
        );
    },
    array('authenticator', 'params')
);
