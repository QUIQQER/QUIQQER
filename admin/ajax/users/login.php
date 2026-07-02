<?php

use QUI\Interfaces\Users\User;
use QUI\System\Log;

QUI::$Ajax->registerFunction(
    'ajax_users_login',
    static function ($authenticator, $params, $authStep, null | string | array $authenticators = null) {
        QUI::getEvents()->fireEvent('userLoginAjaxStart');
        QUI::getSession()->set('inAuthentication', 1);

        if (is_string($authenticators)) {
            $authenticators = json_decode($authenticators, true);
        }

        if (!is_array($authenticators)) {
            $authenticators = [];
        }

        if ($authStep === 'primary' || empty($authStep)) {
            if (QUI::isFrontend()) {
                $allowedPrimaryAuthenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalFrontendAuthenticators();
            } else {
                $allowedPrimaryAuthenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalBackendAuthenticators();
            }

            if (!empty($authenticators)) {
                $allowedPrimaryAuthenticators = array_values(array_intersect(
                    $allowedPrimaryAuthenticators,
                    $authenticators
                ));
            }

            if (!in_array($authenticator, $allowedPrimaryAuthenticators, true)) {
                throw new QUI\Users\Auth\Exception(
                    ['quiqqer/core', 'exception.authenticator.not.found'],
                    404
                );
            }
        }

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
            QUI::getSession()->set('auth-primary', 1);
            QUI::getSession()->set('auth-secondary', 0);

            $PrimaryAuthenticator = new $authenticator();

            if ($PrimaryAuthenticator->satisfiesSecondaryAuthentication()) {
                QUI::getSession()->set('auth-secondary', 1);
            }
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
        if ($secondaryLoginType === 2 && QUI::getSession()->get('auth-primary')) {
            QUI::getSession()->set('auth', 1);
        }

        $Login = new QUI\Users\Controls\Login([
            'authenticators' => $authenticators
        ]);
        $next = $Login->next();
        $loggedIn = false;
        if (
            empty($next) && $secondaryLoginType !== 1
            ||
            QUI::getSession()->get('auth-primary') === 1 && QUI::getSession()->get('auth-secondary') === 1
        ) {
            try {
                QUI::getUsers()->login();
                $loggedIn = true;
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
            'loggedIn' => $loggedIn,
            'authStep' => $Login->getAttribute('authStep'),
            'user' => [
                'id' => $SessionUser->getUUID(),
                'name' => $SessionUser->getName(),
                'lang' => $SessionUser->getLang()
            ]
        ];
    },
    ['authenticator', 'params', 'authStep', 'authenticators']
);
