<?php

/**
 * This file contains
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

        foreach ($authenticator as $auth) {
            $Control = forward_static_call(array($auth, 'getLoginControl'));

            if (is_null($Control)) {
                continue;
            }

            if (!empty($result)) {
                $result .= '<div>or</div>';
            }

            $result .= '<form method="POST" name="login" data-authenticator="' . $auth . '"' . $isGlobalAuth . '>' .
                       $Control->create() .
                       '</form>';
        }

        return $result;
    }

    /**
     * Return the next Authenticator, if one exists
     *
     * @return string|null
     */
    public function next()
    {
        $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalAuthenticators();
        $globals        = array();

        if (QUI::getSession()->get('auth-globals') != 1) {
            foreach ($authenticators as $auth) {
                if (QUI::getSession()->get('auth-' . $auth) !== 1) {
                    $globals[] = $auth;
                }
            }

            $this->isGlobalAuth = true;
        }

        if (!empty($globals)) {
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
            if (QUI::getSession()->get('auth-' . get_class($Authenticator)) !== 1) {
                return get_class($Authenticator);
            }
        }

        return null;
    }
}
