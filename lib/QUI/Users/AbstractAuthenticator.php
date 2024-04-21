<?php

/**
 * This file contains \QUI\Users\AbstractAuthenticator
 */

namespace QUI\Users;

use QUI;
use QUI\Control;
use QUI\System\Console;

/**
 * Parent class for external Authenticator
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
abstract class AbstractAuthenticator implements QUI\Users\AuthenticatorInterface
{
    /**
     * Return the login control
     *
     * @return Control|null
     */
    public static function getLoginControl(): ?Control
    {
        return null;
    }

    /**
     * Return the password reset control
     *
     * @return Control|null
     */
    public static function getPasswordResetControl(): ?Control
    {
        return null;
    }

    /**
     * Return the settings control (eq: for administration)
     *
     * @return Control|null
     */
    public static function getSettingsControl(): ?Control
    {
        return null;
    }

    /**
     * @return bool
     */
    public static function isCLICompatible(): bool
    {
        return false;
    }

    /**
     * Return the ID of the user
     *
     * @return integer
     */
    public function getUserId(): int
    {
        return $this->getUser()->getId();
    }

    /**
     * Return the UUID of the user
     *
     * @return string
     */
    public function getUserUUID(): string
    {
        return $this->getUser()->getUUID();
    }

    /**
     * The CLI Authentication, only if isCLICompatible returns true
     *
     * @param Console $Console
     */
    public function cliAuthentication(Console $Console): void
    {
    }
}
