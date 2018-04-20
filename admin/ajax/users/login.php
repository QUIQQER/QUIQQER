<?php

/**
 *
 */
QUI::$Ajax->registerFunction(
    'ajax_users_login',
    function ($authenticator, $params, $globalauth) {
        QUI::getSession()->set('inAuthentication', 1);

        $User = QUI::getUserBySession();

        if ($User->getId()) {
            QUI::getSession()->remove('inAuthentication');
        }

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
            try {
                QUI::getUsers()->login();
            } catch (\Exception $Exception) {
                // User cannot log in (e.g. User is not active)
                QUI::getSession()->destroy();
                throw $Exception;
            }
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
