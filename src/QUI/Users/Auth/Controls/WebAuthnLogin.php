<?php

namespace QUI\Users\Auth\Controls;

use QUI;
use QUI\Control;

class WebAuthnLogin extends Control
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-webauthn-auth');
        $this->addCSSFile(__DIR__ . '/WebAuthnLogin.css');
        $this->setJavaScriptControl('controls/users/auth/WebAuthnLogin');
    }

    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        return $Engine->fetch(__DIR__ . '/WebAuthnLogin.html');
    }
}
