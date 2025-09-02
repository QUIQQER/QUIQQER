<?php

/**
 * This file contains
 */

namespace QUI\Users\Auth\Controls\Settings;

use QUI;
use QUI\Control;

class VerifiedMail2FA extends Control
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-mail2fa-auth');
        $this->addCSSFile(__DIR__ . '/VerifiedMail2FA.css');
        $this->setJavaScriptControl('controls/users/auth/settings/VerifiedMail2FA');
    }

    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $user = $this->getAttribute('user');
        $mailIsVerified = false;
        $hasAlreadyAuthenticated = false;

        if ($user instanceof QUI\Interfaces\Users\User) {
            $email = $user->getAttribute('email');

            if (method_exists($user, 'isAttributeVerified')) {
                $mailIsVerified = $user->isAttributeVerified(
                    $email,
                    QUI\Users\Attribute\Verifiable\MailAttribute::class
                );
            }

            if ($user->hasAuthenticator(QUI\Users\Auth\VerifiedMail2FA::class)) {
                $hasAlreadyAuthenticated = true;
            }
        }

        $Engine->assign([
            'mailIsVerified' => $mailIsVerified,
            'hasAlreadyAuthenticated' => $hasAlreadyAuthenticated,
            'isBackend' => QUI::isBackend()
        ]);

        return $Engine->fetch(__DIR__ . '/VerifiedMail2FA.html');
    }
}
