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

        $Control = forward_static_call(array($authenticator, 'getLoginControl'));

        if (is_null($Control)) {
            return '';
        }

        return '<form name="login" data-authenticator="' . $authenticator . '">' .
               $Control->create() .
               '</form>';
    }

    /**
     * Return the next Authenticator, if one exists
     *
     * @return string|null
     */
    public function next()
    {
        $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalAuthenticators();

        foreach ($authenticators as $auth) {
            if (QUI::getSession()->get('auth-' . $auth) !== 1) {
                return $auth;
            }
        }

        // test user authenticators
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
