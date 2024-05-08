<?php

/**
 * This file contains
 */

namespace QUI\Users\Auth\Controls;

use QUI;
use QUI\Control;

/**
 * Class QUIQQERLogin
 */
class QUIQQERLogin extends Control
{
    /**
     * QUIQQERLogin constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-login-auth');
        $this->addCSSFile(__DIR__ . '/QUIQQERLogin.css');

        $this->setJavaScriptControl('controls/users/auth/QUIQQERLogin');
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $showPasswordReset = false;

        if (QUI\Users\Auth\Handler::getInstance()->isQuiqqerVerificationPackageInstalled()) {
            if (!empty($_REQUEST['isAdminLogin']) || QUI::isBackend()) {
                $showPasswordReset = (bool)QUI::conf('auth_settings', 'showResetPasswordBackend');
            } else {
                $showPasswordReset = (bool)QUI::conf('auth_settings', 'showResetPasswordFrontend');
            }
        }

        $Engine->assign([
            'usernameText' => QUI::getLocale()->get('quiqqer/core', 'username'),
            'passwordText' => QUI::getLocale()->get('quiqqer/core', 'password'),
            'showPasswordReset' => $showPasswordReset
        ]);

        return $Engine->fetch(__DIR__ . '/QUIQQERLogin.html');
    }
}
