<?php

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_getSecondaryAuthenticators',
    static function () {
        $Auth = QUI\Users\Auth\Handler::getInstance();

        if (QUI::isFrontend()) {
            $authenticators = $Auth->getGlobalFrontendSecondaryAuthenticators();
        } else {
            $authenticators = $Auth->getGlobalBackendSecondaryAuthenticators();
        }

        return array_map(function ($authenticator) use ($Auth) {
            $instance = $Auth->getAuthenticator($authenticator);
            $settings = $instance->getSettingsControl();

            return [
                'title' => $instance->getTitle(),
                'description' => $instance->getDescription(),
                'authenticator' => $authenticator,
                'frontend' => [
                    'title' => $instance->getFrontendTitle(),
                    'description' => $instance->getFrontendDescription()
                ],
                'hasSettings' => !empty($settings)
            ];
        }, $authenticators);
    }
);
