<?php

namespace QUI\Users\Auth\WebAuthn;

use QUI;
use ReportUri\Passkeys\WebAuthn;

use function base64_decode;
use function base64_encode;
use function parse_url;
use function preg_replace;
use function rtrim;
use function str_contains;
use function strlen;
use function str_repeat;
use function strtolower;
use function strtr;
use function time;
use function trim;

use const PHP_URL_HOST;

class Server
{
    private const CHALLENGE_TTL = 300;
    private const SESSION_CREATE = 'quiqqer.webauthn.create';
    private const SESSION_GET = 'quiqqer.webauthn.get';

    private CredentialRepository $credentials;

    public function __construct(?CredentialRepository $credentials = null)
    {
        $this->credentials = $credentials ?? new CredentialRepository();
    }

    /**
     * @return array<string, mixed>
     */
    public function getRegistrationOptions(
        QUI\Interfaces\Users\User $User,
        ?string $credentialName = null,
        ?string $userHandle = null
    ): array {
        $webAuthn = $this->createWebAuthn();
        $existing = [];
        $credentials = $this->credentials->findByUserUuid((string)$User->getUUID());

        if (empty($userHandle) && !empty($credentials[0]['userHandle'])) {
            $userHandle = $credentials[0]['userHandle'];
        }

        $userHandle ??= $this->credentials->createUserHandle();

        foreach ($credentials as $credential) {
            $existing[] = $credential['credentialId'];
        }

        $options = $webAuthn->getCreateArgs(
            $this->credentials->userHandleToBinary($userHandle),
            $this->getWebAuthnAccountName($User->getUsername()),
            $this->getWebAuthnDisplayName($User->getUsername(), $User->getName()),
            120,
            'required',
            'required',
            null,
            $existing
        );

        QUI::getSession()->set(self::SESSION_CREATE, [
            'challenge' => $webAuthn->getChallenge(),
            'userUuid' => $User->getUUID(),
            'userHandle' => $userHandle,
            'credentialName' => $credentialName ?: '',
            'created' => time()
        ]);

        return [
            'publicKey' => $options->publicKey
        ];
    }

    private function getWebAuthnAccountName(string $username): string
    {
        if (str_contains($username, '@')) {
            return $username;
        }

        return $username . '@' . $this->getRpId();
    }

    private function getWebAuthnDisplayName(string $username, string $displayName): string
    {
        $displayName = trim($displayName);

        if ($displayName !== '' && $displayName !== $username) {
            return $displayName;
        }

        return $this->getWebAuthnAccountName($username);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRegistrationOptionsForNewUser(
        string $username,
        string $displayName,
        ?string $credentialName = null
    ): array {
        $userHandle = $this->credentials->createUserHandle();
        $webAuthn = $this->createWebAuthn();

        $options = $webAuthn->getCreateArgs(
            $this->credentials->userHandleToBinary($userHandle),
            $this->getWebAuthnAccountName($username),
            $this->getWebAuthnDisplayName($username, $displayName),
            120,
            'required',
            'required'
        );

        QUI::getSession()->set(self::SESSION_CREATE, [
            'challenge' => $webAuthn->getChallenge(),
            'userUuid' => null,
            'userHandle' => $userHandle,
            'username' => $username,
            'credentialName' => $credentialName ?: '',
            'created' => time()
        ]);

        return [
            'publicKey' => $options->publicKey,
            'userHandle' => $userHandle
        ];
    }

    /**
     * @param array<string, mixed> $attestation
     * @return array<string, mixed>
     */
    public function finishRegistrationForUser(
        QUI\Interfaces\Users\User $User,
        array $attestation,
        ?string $credentialName = null
    ): array {
        $state = QUI::getSession()->get(self::SESSION_CREATE);

        if (empty($state) || !is_array($state)) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.challenge_missing'],
                400
            );
        }

        $this->assertStateIsFresh(self::SESSION_CREATE, $state);

        if (!empty($state['userUuid']) && $state['userUuid'] !== $User->getUUID()) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.challenge_invalid_user'],
                400
            );
        }

        if (!empty($state['username']) && $state['username'] !== $User->getUsername()) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.challenge_invalid_user'],
                400
            );
        }

        $credential = $this->processCreate($state, $attestation);

        $this->credentials->create(
            (string)$User->getUUID(),
            $state['userHandle'],
            $credential->credentialId,
            $credential->credentialPublicKey,
            $credential->signatureCounter,
            $credential->AAGUID,
            $attestation['transports'] ?? [],
            trim((string)($credentialName ?: ($state['credentialName'] ?? ''))),
            (bool)($credential->isBackupEligible ?? false),
            (bool)($credential->isBackedUp ?? false)
        );

        QUI::getSession()->remove(self::SESSION_CREATE);

        return [
            'credentialId' => $this->base64UrlEncode($credential->credentialId)
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAuthenticationOptions(?QUI\Interfaces\Users\User $User = null): array
    {
        $webAuthn = $this->createWebAuthn();
        $credentialIds = [];
        $userUuid = null;

        if ($User) {
            $userUuid = (string)$User->getUUID();

            foreach ($this->credentials->findByUserUuid($userUuid) as $credential) {
                $credentialIds[] = $credential['credentialId'];
            }
        }

        $options = $webAuthn->getGetArgs(
            $credentialIds,
            120,
            true,
            true,
            true,
            true,
            true,
            'required'
        );

        QUI::getSession()->set(self::SESSION_GET, [
            'challenge' => $webAuthn->getChallenge(),
            'userUuid' => $userUuid,
            'created' => time()
        ]);

        return [
            'publicKey' => $options->publicKey
        ];
    }

    /**
     * @param array<string, mixed> $assertion
     * @return array<string, mixed>
     */
    public function finishAuthentication(array $assertion): array
    {
        $state = QUI::getSession()->get(self::SESSION_GET);

        if (empty($state) || !is_array($state)) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.challenge_missing'],
                400
            );
        }

        $this->assertStateIsFresh(self::SESSION_GET, $state);

        $credentialId = $this->base64UrlDecode($assertion['id'] ?? '');
        $credential = $this->credentials->findByCredentialId($credentialId);

        if (!$credential) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.credential_not_found'],
                404
            );
        }

        if (!empty($state['userUuid']) && $state['userUuid'] !== $credential['userUuid']) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.challenge_invalid_user'],
                400
            );
        }

        $userHandle = $this->base64UrlDecode($assertion['response']['userHandle'] ?? '');

        if ($userHandle !== '' && $userHandle !== $this->credentials->userHandleToBinary($credential['userHandle'])) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.challenge_invalid_user'],
                400
            );
        }

        $webAuthn = $this->createWebAuthn();
        $webAuthn->processGet(
            $this->base64UrlDecode($assertion['response']['clientDataJSON'] ?? ''),
            $this->base64UrlDecode($assertion['response']['authenticatorData'] ?? ''),
            $this->base64UrlDecode($assertion['response']['signature'] ?? ''),
            $credential['publicKey'],
            $state['challenge'],
            $credential['signCount'],
            true
        );

        $this->credentials->updateUsage($credential['id'], $webAuthn->getSignatureCounter());
        QUI::getSession()->remove(self::SESSION_GET);

        return $credential;
    }

    public function getCredentialRepository(): CredentialRepository
    {
        return $this->credentials;
    }

    public function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;

        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value, true) ?: '';
    }

    public function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $attestation
     */
    private function processCreate(array $state, array $attestation): \stdClass
    {
        $webAuthn = $this->createWebAuthn();

        return $webAuthn->processCreate(
            $this->base64UrlDecode($attestation['response']['clientDataJSON'] ?? ''),
            $this->base64UrlDecode($attestation['response']['attestationObject'] ?? ''),
            $state['challenge'],
            true,
            true
        );
    }

    /**
     * @param array<string, mixed> $state
     */
    private function assertStateIsFresh(string $sessionKey, array $state): void
    {
        if (empty($state['created']) || time() - (int)$state['created'] > self::CHALLENGE_TTL) {
            QUI::getSession()->remove($sessionKey);

            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.challenge_missing'],
                400
            );
        }
    }

    private function createWebAuthn(): WebAuthn
    {
        return new WebAuthn(
            QUI::conf('globals', 'title') ?: 'QUIQQER',
            $this->getRpId(),
            true
        );
    }

    private function getRpId(): string
    {
        $Request = QUI::getRequest();
        $host = $Request->getHost();

        if ($host === '') {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        }

        $host = parse_url('https://' . $host, PHP_URL_HOST) ?: $host;
        $host = strtolower(trim($host, '.'));

        return preg_replace('/:\d+$/', '', $host) ?: 'localhost';
    }
}
