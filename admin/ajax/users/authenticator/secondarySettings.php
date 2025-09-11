<?php

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_secondarySettings',
    static function ($authenticator): string {
        $available = QUI\Users\Auth\Handler::getInstance()->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator]) && $available[$authenticator]) {
            return '';
        }

        $instance = new $authenticator();

        if (!$instance->isSecondaryAuthentication()) {
            return '';
        }

        $settings = $instance->getSettingsControl();
        $Output = new QUI\Output();
        $control = '';
        $css = QUI\Control\Manager::getCSS();

        if ($settings) {
            $control = $settings->create();
        }

        return $Output->parse($css . $control);
    },
    ['authenticator']
);
