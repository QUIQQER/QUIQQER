<?php

/**
 * This file contains
 */

namespace QUI\Users\Auth\Controls;

use QUI;
use QUI\Control;

/**
 * Class QUIQQERLogin
 *
 * @package QUI
 */
class QUIQQERLogin extends Control
{
    /**
     * QUIQQERLogin constructor.
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-login-auth');
        $this->addCSSFile(dirname(__FILE__) . '/QUIQQERLogin.css');

        $this->setJavaScriptControl('controls/users/auth/QUIQQERLogin');
    }

    /**
     * @return string
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $showPasswordReset = false;

        if (QUI\Users\Auth\Handler::getInstance()->isQuiqqerVerificationPackageInstalled()) {
            if (!empty($_REQUEST['isAdminLogin']) || QUI::isBackend()) {
                $showPasswordReset = boolval(QUI::conf('auth_settings', 'showResetPasswordBackend'));
            } else {
                $showPasswordReset = boolval(QUI::conf('auth_settings', 'showResetPasswordFrontend'));
            }
        }

        $Engine->assign(array(
            'usernameText'      => QUI::getLocale()->get('quiqqer/quiqqer', 'username'),
            'passwordText'      => QUI::getLocale()->get('quiqqer/quiqqer', 'password'),
            'showPasswordReset' => $showPasswordReset
        ));

        return $Engine->fetch(dirname(__FILE__) . '/QUIQQERLogin.html');
    }
}
