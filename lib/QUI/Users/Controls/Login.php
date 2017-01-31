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
            'data-qui' => 'controls/users/Login',
            'nodeName' => 'form'
        ));
    }

    /**
     * @return string
     */
    public function getBody()
    {
        $authenticator = $this->next();

        if (is_null($authenticator)) {
            $this->setAttribute('data-authenticator', false);
            return '';
        }

        $this->setAttribute('data-authenticator', $authenticator);

        $Control = forward_static_call(array($authenticator, 'getLoginControl'));

        if (is_null($Control)) {
            return '';
        }

        return $Control->create();
    }

    /**
     * Return the next Authenticator, if one exists
     *
     * @return QUI\Users\AuthInterface|null
     */
    public function next()
    {
        $authenticators = QUI::getUsers()->getAuthenticators();

        foreach ($authenticators as $auth) {
            if (QUI::getSession()->get('auth-' . $auth) !== 1) {
                return $auth;
            }
        }

        return null;
    }
}
