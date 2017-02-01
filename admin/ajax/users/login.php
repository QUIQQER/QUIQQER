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

        $control = '';
        $control .= $Login->create();
        $control .= QUI\Control\Manager::getCSS();

        return array(
            'authenticator' => $Login->next(),
            'control'       => $control
        );
    },
    array('authenticator', 'params')
);
