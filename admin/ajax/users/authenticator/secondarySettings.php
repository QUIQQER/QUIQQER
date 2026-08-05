<?php

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_secondarySettings',
    static function ($authenticator): string {
        $available = QUI\Users\Auth\Handler::getInstance()->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator]) && $available[$authenticator]) {
            return '';
        }

        // check if 1fa is done
        $Session = QUI::getSession();
        $User = QUI::getUserBySession();
        $AuthenticatorUser = null;
        $activatedExistingCredentials = false;

        if (!QUI::getUsers()->isNobodyUser($User)) {
            $AuthenticatorUser = $User;
            $instance = new $authenticator($User);
        } elseif ($Session->get('auth-primary')) {
            $uid = $Session->get('uid');
            $instance = new $authenticator($uid);

            try {
                $AuthenticatorUser = QUI::getUsers()->get($uid);
            } catch (QUI\Exception) {
            }
        } else {
            $instance = new $authenticator();
        }

        if (!$instance->isSecondaryAuthentication()) {
            return '';
        }

        if (
            $authenticator === QUI\Users\Auth\WebAuthn::class
            && $AuthenticatorUser instanceof QUI\Interfaces\Users\User
        ) {
            $credentials = (new QUI\Users\Auth\WebAuthn\CredentialRepository())
                ->findByUserUuid((string)$AuthenticatorUser->getUUID());

            if (!empty($credentials)) {
                if (!$AuthenticatorUser->hasAuthenticator(QUI\Users\Auth\WebAuthn::class)) {
                    $AuthenticatorUser->enableAuthenticator(QUI\Users\Auth\WebAuthn::class);
                    $activatedExistingCredentials = true;
                }
            }
        }

        $settings = $instance->getSettingsControl();
        $Output = new QUI\Output();
        $control = '';
        $css = QUI\Control\Manager::getCSS();

        if ($settings) {
            $settings->setAttribute('activationMode', true);
            $settings->setAttribute('activatedExistingCredentials', $activatedExistingCredentials);
            $control = $settings->create();
        }

        return $Output->parse($css . $control);
    },
    ['authenticator']
);
