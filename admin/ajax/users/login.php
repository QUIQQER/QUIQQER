<?php

/**
 *
 */
QUI::$Ajax->registerFunction(
    'ajax_users_login',
    function ($authenticator, $params, $globalauth) {
        QUI::getEvents()->fireEvent('userLoginAjaxStart');

        QUI::getSession()->destroy();
        QUI::getSession()->set('inAuthentication', 1);

        $User = QUI::getUserBySession();

        if ($User->getId()) {
            QUI::getSession()->remove('inAuthentication');
        }

        try {
            QUI::getUsers()->authenticate(
                $authenticator,
                \json_decode($params, true)
            );
        } catch (QUI\Exception $Exception) {
            if ($Exception->getCode() === 429) {
                throw new QUI\Users\UserAuthException(
                    [
                        'quiqqer/quiqqer',
                        'exception.login.fail.login_locked'
                    ],
                    $Exception->getCode()
                );
            }

            throw new QUI\Users\UserAuthException(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }


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

        $SessionUser = QUI::getUserBySession();

        return [
            'authenticator' => $Login->next(),
            'control'       => $control,
            'user'          => [
                'id' => $SessionUser->getId(),
                'name' => $SessionUser->getName(),
                'lang' => $SessionUser->getLang()
            ]
        ];
    },
    ['authenticator', 'params', 'globalauth']
);
