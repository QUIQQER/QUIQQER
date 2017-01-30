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
    }

    /**
     * @return string
     */
    public function getBody()
    {
        $Control = $this->next();

        if (is_null($Control)) {
            return '';
        }

        return $Control->create();
    }

    /**
     * Shows next authentication
     *
     * @return QUI\Control
     */
    public function next()
    {
        $authenticators = QUI::getUsers()->getAuthenticators();

        foreach ($authenticators as $auth) {
            if (QUI::getSession()->get('auth-' . $auth) !== 1) {
                return forward_static_call(array($auth, 'getLoginControl'));
            }
        }

        return null;
    }
}
