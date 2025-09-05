<?php

use QUI\Interfaces\Users\User;
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
            QUI::getSession()->set('auth-secondary', 0);
        }

        if ($authStep === 'secondary') {
            QUI::getSession()->set('auth-secondary', 1);
        }

        if (QUI::isFrontend()) {
            $secondaryLoginType = (int)QUI::conf('auth_settings', 'secondary_frontend');
        } else {
            $secondaryLoginType = (int)QUI::conf('auth_settings', 'secondary_backend');
        }

        // $secondaryLoginType = 0 no 2fa
        // $secondaryLoginType = 1 2fa is required
        // $secondaryLoginType = 2 2fa is optional

        $Login = new QUI\Users\Controls\Login();
        $next = $Login->next();

        if (
            empty($next) && $secondaryLoginType !== 1
            ||
            QUI::getSession()->get('auth-globals') === 1
            && QUI::getSession()->get('auth-secondary') === 1
        ) {
            try {
                QUI::getUsers()->login();
            } catch (\Exception $Exception) {
                // User cannot log in (e.g. User is not active)
                QUI::getSession()->destroy();
                throw $Exception;
            }
        }


        // result
        $SessionUser = QUI::getUserBySession();

        $control = $Login->create();
        $control .= QUI\Control\Manager::getCSS();


        return [
            'authenticator' => $next,
            'secondaryLoginType' => $secondaryLoginType,
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
