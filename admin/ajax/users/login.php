<?php

use QUI\System\Log;

QUI::$Ajax->registerFunction(
    'ajax_users_login',
    static function ($authenticator, $params, $authStep) {
        QUI::getEvents()->fireEvent('userLoginAjaxStart');

        if ($authStep !== 'secondary') {
            //QUI::getSession()->destroy();
        }

        QUI::getSession()->set('inAuthentication', 1);

        $User = QUI::getUserBySession();

        if ($User->getUUID()) {
            QUI::getSession()->remove('inAuthentication');
        }

        try {
            QUI::getUsers()->authenticate(
                $authenticator,
                json_decode($params, true)
            );
        } catch (QUI\Users\UserAuthException | QUI\Users\Auth\Exception | QUI\Users\Exception $Exception) {
            if ($Exception->getCode() === 429) {
                throw new QUI\Users\UserAuthException(
                    ['quiqqer/core', 'exception.login.fail.login_locked'],
                    $Exception->getCode()
                );
            }

            throw $Exception;
        } catch (\Exception $Exception) {
            Log::writeException($Exception);

            throw new QUI\Users\UserAuthException(
                ['quiqqer/core', 'exception.login.fail'],
                $Exception->getCode()
            );
        }

        if ($authStep === 'primary' || empty($authStep)) {
            QUI::getSession()->set('auth-globals', 1);
        }

        if ($authStep === 'secondary') {
            QUI::getSession()->set('auth-secondary', 1);
        }

        $Login = new QUI\Users\Controls\Login();
        $next = $Login->next();

        if (empty($next)) {
            try {
                QUI::getUsers()->login();
            } catch (\Exception $Exception) {
                // User cannot log in (e.g. User is not active)
                QUI::getSession()->destroy();
                throw $Exception;
            }
        }

        $control = $Login->create();
        $control .= QUI\Control\Manager::getCSS();

        $SessionUser = QUI::getUserBySession();

        return [
            'authenticator' => $Login->next(),
            'control' => $control,
            'authStep' => $Login->getAttribute('authStep'),
            'user' => [
                'id' => $SessionUser->getUUID(),
                'name' => $SessionUser->getName(),
                'lang' => $SessionUser->getLang()
            ]
        ];
    },
    ['authenticator', 'params', 'authStep']
);
