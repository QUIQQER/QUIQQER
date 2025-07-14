<?php

/**
 * This file contains
 */

namespace QUI\Users\Auth\Controls;

use QUI;
use QUI\Control;

class VerifiedMail2FA extends Control
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-mail2fa-auth');
        $this->addCSSFile(__DIR__ . '/VerifiedMail2FA.css');
        $this->setJavaScriptControl('controls/users/auth/VerifiedMail2FA');
    }

    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        return $Engine->fetch(__DIR__ . '/VerifiedMail2FA.html');
    }
}
