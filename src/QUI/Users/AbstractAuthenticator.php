<?php

/**
 * This file contains \QUI\Users\AbstractAuthenticator
 */

namespace QUI\Users;

use QUI;
use QUI\Control;
use QUI\Locale;
use QUI\System\Console;

/**
 * Parent class for external Authenticator
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
abstract class AbstractAuthenticator implements QUI\Users\AuthenticatorInterface
{
    public static function getLoginControl(): ?Control
    {
        return null;
    }

    public function getPasswordResetControl(): ?Control
    {
        return null;
    }

    public function getSettingsControl(): ?Control
    {
        return null;
    }

    public static function isCLICompatible(): bool
    {
        return false;
    }

    public function getFrontendTitle(null | Locale $Locale = null): string
    {
        return $this->getTitle($Locale);
    }

    public function getFrontendDescription(null | Locale $Locale = null): string
    {
        return $this->getDescription($Locale);
    }

    public function getUserId(): int
    {
        return $this->getUser()->getId();
    }

    public function getUserUUID(): string
    {
        return $this->getUser()->getUUID();
    }

    public function cliAuthentication(Console $Console): void
    {
    }

    public function isPrimaryAuthentication(): bool
    {
        return true;
    }

    public function isSecondaryAuthentication(): bool
    {
        return true;
    }
}
