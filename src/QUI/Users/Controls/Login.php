<?php

/**
 * This file contains QUI\Users\Controls\Login
 */

namespace QUI\Users\Controls;

use QUI;
use QUI\Control;

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
            'authStep' => 'primary',
            
            // predefined list of Authenticator classes; if empty = use all authenticators
            // that are configured
            'authenticators' => [],

        ]);

        parent::__construct($options);

        $this->addCSSClass('quiqqer-login ');
        $this->setJavaScriptControl('controls/users/Login');
    }

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
     */
    public function next(): array | null
    {
        if (QUI::getSession()->get('auth-globals') !== 1) {
            // primary authenticator
            if (QUI::isFrontend()) {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalFrontendAuthenticators();
            } else {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalBackendAuthenticators();
            }

            $this->setAttribute('authStep', 'primary');
        }

        if (empty($authenticators) && QUI::getSession()->get('auth-secondary') !== 1) {
            // secondary authenticators
            if (QUI::isFrontend()) {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalFrontendSecondaryAuthenticators();
            } else {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalBackendSecondaryAuthenticators();
            }

            $this->setAttribute('authStep', 'secondary');
        }

        if (empty($authenticators)) {
            return null;
        }

        // sort globals (QUIQQER Login has to be first!)
        usort($authenticators, static function ($a, $b): int {
            if ($a === QUI\Users\Auth\QUIQQER::class) {
                return -1;
            }

            if ($b === QUI\Users\Auth\QUIQQER::class) {
                return 1;
            }

            return 0;
        });

        return $authenticators;
    }
}
