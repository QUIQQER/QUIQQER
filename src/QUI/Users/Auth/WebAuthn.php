<?php

namespace QUI\Users\Auth;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Users\AbstractAuthenticator;
use QUI\Users\Auth\WebAuthn\Server;
use QUI\Utils\Security\Orthos;

use function is_array;
use function is_null;
use function is_string;
use function json_decode;
use function str_contains;

class WebAuthn extends AbstractAuthenticator
{
    protected ?User $User = null;
    protected ?string $user = null;

    /**
     * @param array<array-key, mixed>|int|string|User|null $user
     */
    public function __construct(null | array | int | string | User $user = null)
    {
        if (empty($user)) {
            return;
        }

        if ($user instanceof User) {
            $this->User = $user;
            return;
        }

        $this->user = Orthos::clear($user);
    }

    public static function getLoginControl(): ?QUI\Control
    {
        return new Controls\WebAuthnLogin();
    }

    public function satisfiesSecondaryAuthentication(): bool
    {
        return true;
    }

    public function getSettingsControl(): ?QUI\Control
    {
        $user = null;

        try {
            $user = $this->getUser();
        } catch (QUI\Exception) {
        }

        return new Controls\Settings\WebAuthn([
            'user' => $user
        ]);
    }

    public function getTitle(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.webauthn.title');
    }

    public function getDescription(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.webauthn.description');
    }

    public function getFrontendTitle(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.webauthn.frontend.title');
    }

    public function getFrontendDescription(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.webauthn.frontend.description');
    }

    public function getIcon(): string
    {
        return 'fa fa-fingerprint';
    }

    public function getUser(): User
    {
        if (!is_null($this->User)) {
            return $this->User;
        }

        if (!empty($this->user)) {
            if (QUI::conf('globals', 'emaillogin') && str_contains($this->user, '@')) {
                try {
                    $this->User = QUI::getUsers()->getUserByMail($this->user);
                    return $this->User;
                } catch (QUI\Exception) {
                }
            }

            try {
                $this->User = QUI::getUsers()->get($this->user);
                return $this->User;
            } catch (QUI\Exception) {
            }

            try {
                $this->User = QUI::getUsers()->getUserByName($this->user);
                return $this->User;
            } catch (QUI\Exception) {
            }
        }

        throw new QUI\Users\Exception(
            ['quiqqer/core', 'exception.login.fail.user.not.found'],
            404
        );
    }

    /**
     * @param string|int|array<string, mixed> $authParams
     */
    public function auth(string | int | array $authParams): bool
    {
        if (is_string($authParams)) {
            $authParams = json_decode($authParams, true);
        }

        if (!is_array($authParams)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.webauthn.assertion_missing'],
                401
            );
        }

        if (isset($authParams['assertion']) && is_string($authParams['assertion'])) {
            $authParams['assertion'] = json_decode($authParams['assertion'], true);
        }

        if (empty($authParams['assertion']) || !is_array($authParams['assertion'])) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.webauthn.assertion_missing'],
                401
            );
        }

        try {
            $credential = (new Server())->finishAuthentication($authParams['assertion']);
            $this->User = QUI::getUsers()->get($credential['userUuid']);
        } catch (QUI\Users\Exception $Exception) {
            throw $Exception;
        } catch (\Throwable $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail'],
                401
            );
        }

        return true;
    }
}
