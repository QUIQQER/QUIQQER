<?php

/**
 * This file contains \QUI\Users\AuthenticatorInterface
 */

namespace QUI\Users;

use QUI\Control;
use QUI\Locale;
use QUI\System\Console;

/**
 * Interface for external authentication
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
interface AuthenticatorInterface
{
    /**
     * @param array|integer|string $user - name of the user, or user id
     */
    public function __construct(array|int|string $user = '');

    /**
     * Return the login control
     *
     * @return Control|null
     */
    public static function getLoginControl(): ?Control;

    /**
     * Return the password reset control
     *
     * @return Control|null
     */
    public static function getPasswordResetControl(): ?Control;

    /**
     * Return the settings control (eq: for administration)
     *
     * @return Control|null
     */
    public static function getSettingsControl(): ?Control;

    /**
     * @return bool
     */
    public static function isCLICompatible(): bool;

    /**
     * Authenticate the user
     *
     * @param string|array|integer $authParams
     *
     * @throws Exception
     */
    public function auth(string|array|int $authParams);

    /**
     * Return the user object
     *
     * @return \QUI\Interfaces\Users\User
     */
    public function getUser(): \QUI\Interfaces\Users\User;

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getTitle(Locale $Locale = null): string;

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(Locale $Locale = null): string;

    /**
     * CLI
     */

    /**
     * Return the quiqqer user id
     *
     * @return integer|boolean
     */
    public function getUserId(): bool|int;

    /**
     * The CLI Authentication, only if isCLICompatible returns true
     *
     * @param Console $Console
     */
    public function cliAuthentication(Console $Console);
}
