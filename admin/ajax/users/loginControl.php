<?php

/**
 * Return the login control
 *
 * @param array $authenticators - pre-defined list of authenticators [if ommitted use QUIQQER settings]
 */

QUI::$Ajax->registerFunction(
    'ajax_users_loginControl',
    static function ($authenticators = null): array {
        if (empty($authenticators)) {
            $authenticators = [];
        } else {
            $authenticators = json_decode($authenticators, true);
        }

        if (QUI::isFrontend()) {
            $secondaryLoginType = (int)QUI::conf('auth_settings', 'secondary_frontend');
        } else {
            $secondaryLoginType = (int)QUI::conf('auth_settings', 'secondary_backend');
        }

        $Login = new QUI\Users\Controls\Login([
            'authenticators' => $authenticators
        ]);

        $next = $Login->next();

        $control = $Login->create();
        $control .= QUI\Control\Manager::getCSS();

        return [
            'secondaryLoginType' => $secondaryLoginType,
            'authenticator' => $next,
            'control' => $control,
            'authStep' => $Login->getAttribute('authStep')
        ];
    },
    ['authenticators']
);
