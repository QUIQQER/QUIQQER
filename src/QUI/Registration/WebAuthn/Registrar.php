<?php

namespace QUI\Registration\WebAuthn;

use QUI;
use QUI\FrontendUsers;
use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;
use QUI\Users\Auth\WebAuthn\Server;

use function is_array;
use function is_null;
use function json_decode;
use function trim;

class Registrar extends FrontendUsers\AbstractRegistrar
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $attestation = null;

    /**
     * @return array<FrontendUsers\InvalidFormField>
     */
    public function validate(): array
    {
        $username = $this->getUsername();

        if ($username === '') {
            throw new FrontendUsers\Exception([
                'quiqqer/core',
                'exception.webauthn.username_missing'
            ]);
        }

        if (QUI::getUsers()->usernameExists($username)) {
            throw new FrontendUsers\Exception([
                'quiqqer/core',
                'exception.webauthn.username_exists'
            ]);
        }

        $attestation = $this->getAttestation();

        if (empty($attestation)) {
            throw new FrontendUsers\Exception([
                'quiqqer/core',
                'exception.webauthn.attestation_missing'
            ]);
        }

        return [];
    }

    /**
     * @return array<FrontendUsers\InvalidFormField>
     */
    public function getInvalidFields(): array
    {
        return [];
    }

    public function getUsername(): string
    {
        return trim((string)$this->getAttribute('username'));
    }

    public function createUser(): QUI\Interfaces\Users\User
    {
        $User = parent::createUser();
        $SystemUser = QUI::getUsers()->getSystemUser();

        if (str_contains($this->getUsername(), '@')) {
            $User->setAttribute('email', $this->getUsername());
        }

        $User->setPassword(QUI\Security\Password::generateRandom(), $SystemUser);
        $User->save($SystemUser);

        try {
            (new Server())->finishRegistrationForUser(
                $User,
                $this->getAttestation(),
                (string)$this->getAttribute('credentialName')
            );

            $User->enableAuthenticator(WebAuthnAuthenticator::class, $SystemUser);
        } catch (\Throwable $Exception) {
            try {
                $User->delete($SystemUser);
            } catch (\Throwable $DeleteException) {
                QUI\System\Log::writeException($DeleteException);
            }

            throw $Exception;
        }

        return $User;
    }

    public function onRegistered(QUI\Interfaces\Users\User $User): void
    {
    }

    public function getControl(): QUI\Control
    {
        return new Control();
    }

    public function getTitle(null | QUI\Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.webauthn.registrar.title');
    }

    public function getDescription(null | QUI\Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.webauthn.registrar.description');
    }

    public function getIcon(): string
    {
        return 'fa fa-fingerprint';
    }

    public function canSendPassword(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function getAttestation(): array
    {
        if (!is_null($this->attestation)) {
            return $this->attestation;
        }

        $attestation = $this->getAttribute('attestation');

        if (is_string($attestation)) {
            $attestation = json_decode($attestation, true);
        }

        if (!is_array($attestation)) {
            $attestation = [];
        }

        $this->attestation = $attestation;

        return $this->attestation;
    }
}
