<?php

namespace QUI\Registration\WebAuthn;

use QUI;
use QUI\Control as QUIControl;

class Control extends QUIControl
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-webauthn-registration');
        $this->addCSSFile(__DIR__ . '/Control.css');
        $this->setJavaScriptControl('controls/users/auth/WebAuthnRegistration');
    }

    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        return $Engine->fetch(__DIR__ . '/Control.html');
    }
}
