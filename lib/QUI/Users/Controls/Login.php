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
    public function __construct($options = [])
    {
        $this->setAttributes([
            'data-qui'       => 'controls/users/Login',
            'authenticators' => [] // predefined list of Authenticator classes; if empty = use all authenticators
            // that are configured
        ]);

        parent::__construct($options);

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
            $authenticator = [$authenticator];
        }

        $authenticators          = [];
        $exclusiveAuthenticators = $this->getAttribute('authenticators');

        if (empty($exclusiveAuthenticators)) {
            $exclusiveAuthenticators = [];
        }

        foreach ($authenticator as $k => $auth) {
            if (!empty($exclusiveAuthenticators) && !in_array($auth, $exclusiveAuthenticators)) {
                continue;
            }

            $Control = forward_static_call([$auth, 'getLoginControl']);

            if (is_null($Control)) {
                continue;
            }

            $authenticators[] = [
                'class'   => $auth,
                'control' => $Control
            ];
        }

        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'passwordReset'  => !empty($_REQUEST['password_reset']),
            'globalAuth'     => $this->isGlobalAuth,
            'authenticators' => $authenticators,
            'count'          => count($authenticators) - 1
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/Login.html');
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
        $globals        = [];

        if (QUI::getSession()->get('auth-globals') != 1) {
            foreach ($authenticators as $auth) {
                if (QUI::getSession()->get('auth-' . $auth) !== 1) {
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
            if (QUI::getSession()->get('auth-' . get_class($Authenticator)) !== 1) {
                return get_class($Authenticator);
            }
        }

        return null;
    }
}
