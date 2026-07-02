<?php

namespace QUI\Users\Auth\Controls\Settings;

use QUI;
use QUI\Control;
use QUI\Users\Auth\WebAuthn\CredentialRepository;

class WebAuthn extends Control
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-webauthn-auth-settings');
        $this->addCSSClass('default-content');
        $this->addCSSFile(__DIR__ . '/WebAuthn.css');
        $this->setJavaScriptControl('controls/users/auth/settings/WebAuthn');
    }

    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $user = $this->getAttribute('user');
        $credentials = [];
        $userUuid = '';
        $canCreateCredential = false;

        if ($user instanceof QUI\Interfaces\Users\User) {
            $credentials = (new CredentialRepository())->findByUserUuid($user->getUUID());
            $userUuid = $user->getUUID();
            $SessionUser = QUI::getUserBySession();

            if (
                $SessionUser->getUUID() === $user->getUUID()
                || QUI::getSession()->get('uid') === $user->getUUID()
            ) {
                $canCreateCredential = true;
            }
        }

        $Engine->assign([
            'canCreateCredential' => $canCreateCredential,
            'credentials' => $credentials,
            'userUuid' => $userUuid
        ]);

        return $Engine->fetch(__DIR__ . '/WebAuthn.html');
    }
}
