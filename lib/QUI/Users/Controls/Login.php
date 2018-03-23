<?php

/**
 * This file contains QUI\Users\Controls\Login
 */

namespace QUI\Users\Controls;

use QUI;
use QUI\Control;

/**
 * Class Login
 * Main Login Control - Log in an user with all authentications
 *
 * @package QUI
 */
class Login extends Control
{
    /**
     * @var bool
     */
    protected $isGlobalAuth = false;

    /**
     * Login constructor.
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->setAttributes(array(
            'data-qui' => 'controls/users/Login'
        ));

        $this->addCSSClass('quiqqer-login');
    }

    /**
     * @return string
     *
     * @throws QUI\Users\Exception
     */
    public function getBody()
    {
        $authenticator = $this->next();

        if (is_null($authenticator)) {
            return '';
        }

        if (!is_array($authenticator)) {
            $authenticator = array($authenticator);
        }

        $result       = '';
        $isGlobalAuth = '';

        if ($this->isGlobalAuth) {
            $isGlobalAuth = ' data-globalauth="1"';
        }

        if (!empty($_REQUEST['password_reset'])) {
            $result .= '<div class="quiqqer-users-login-success">';
            $result .= QUI::getLocale()->get('quiqqer/system', 'users.auth.passwordresetverification.success');
            $result .= '</div>';
        }

        foreach ($authenticator as $k => $auth) {
            $Control = forward_static_call(array($auth, 'getLoginControl'));

            if (is_null($Control)) {
                continue;
            }

            $result .= '<form method="POST" name="login" data-authenticator="'.$auth.'"'.$isGlobalAuth.'>'.
                       $Control->create().
                       '</form>';

            if (isset($authenticator[$k + 1])) {
                $result .= '<div>';
                $result .= QUI::getLocale()->get('quiqqer/system', 'controls.users.auth.login.or');
                $result .= '</div>';
            }
        }

        return $result;
    }

    /**
     * Return the next Authenticator, if one exists
     *
     * @return array|null
     *
     * @throws QUI\Users\Exception
     */
    public function next()
    {
        $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalAuthenticators();
        $globals        = array();

        if (QUI::getSession()->get('auth-globals') != 1) {
            foreach ($authenticators as $auth) {
                if (QUI::getSession()->get('auth-'.$auth) !== 1) {
                    $globals[] = $auth;
                }
            }

            $this->isGlobalAuth = true;
        }

        if (!empty($globals)) {
            // sort globals (QUIQQER Login has to be first!)
            usort($globals, function ($a, $b) {
                if ($a === QUI\Users\Auth\QUIQQER::class) {
                    return -1;
                }

                if ($b === QUI\Users\Auth\QUIQQER::class) {
                    return 1;
                }

                return 0;
            });

            return $globals;
        }

        // test user authenticators
        // multi authenticators
        $uid = QUI::getSession()->get('uid');

        if (!$uid) {
            return null;
        }

        $User           = QUI::getUsers()->get($uid);
        $authenticators = $User->getAuthenticators();

        foreach ($authenticators as $Authenticator) {
            if (QUI::getSession()->get('auth-'.get_class($Authenticator)) !== 1) {
                return get_class($Authenticator);
            }
        }

        return null;
    }
}
