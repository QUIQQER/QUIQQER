<?php

/**
 * This file contains QUI\Users\Controls\Login
 */

namespace QUI\Users\Controls;

use QUI;
use QUI\Control;
use QUI\Database\Exception;
use QUI\ExceptionStack;

use function count;
use function forward_static_call;
use function in_array;
use function is_array;
use function is_null;
use function usort;

/**
 * Class Login
 * Main Login Control - Log in a user with all authentications
 */
class Login extends Control
{
    protected bool $isGlobalAuth = false;

    public function __construct(array $options = [])
    {
        $this->setAttributes([
            'data-qui' => 'controls/users/Login',
            'authenticators' => [] // predefined list of Authenticator classes; if empty = use all authenticators
            // that are configured
        ]);

        parent::__construct($options);

        $this->addCSSClass('quiqqer-login ');
        $this->setJavaScriptControl('controls/users/Login');
    }

    /**
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $authenticator = $this->next();

        if (is_null($authenticator)) {
            return '';
        }

        if (!is_array($authenticator)) {
            $authenticator = [$authenticator];
        }

        $authenticators = [];
        $exclusiveAuthenticators = $this->getAttribute('authenticators');

        if (empty($exclusiveAuthenticators)) {
            $exclusiveAuthenticators = [];
        }

        foreach ($authenticator as $auth) {
            if (!empty($exclusiveAuthenticators) && !in_array($auth, $exclusiveAuthenticators)) {
                continue;
            }

            $Control = forward_static_call([$auth, 'getLoginControl']);

            if (is_null($Control)) {
                continue;
            }

            $authenticators[] = [
                'class' => $auth,
                'control' => $Control
            ];
        }

        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'passwordReset' => !empty($_REQUEST['password_reset']),
            'globalAuth' => $this->isGlobalAuth,
            'authenticators' => $authenticators,
            'count' => count($authenticators) - 1
        ]);

        return $Engine->fetch(__DIR__ . '/Login.html');
    }

    /**
     * Return the next Authenticator, if one exists
     *
     * @throws Exception
     * @throws QUI\Exception
     * @throws ExceptionStack
     */
    public function next(): array|string|null
    {
        if (QUI::isFrontend()) {
            $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalAuthenticators();
        } else {
            $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalBackendAuthenticators();
        }

        $globals = [];

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
            usort($globals, static function ($a, $b): int {
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

        $User = QUI::getUsers()->get($uid);
        $authenticators = $User->getAuthenticators();

        foreach ($authenticators as $Authenticator) {
            if (QUI::getSession()->get('auth-' . $Authenticator::class) !== 1) {
                return $Authenticator::class;
            }
        }

        return null;
    }
}
