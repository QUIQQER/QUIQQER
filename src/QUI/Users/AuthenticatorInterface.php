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
    public function __construct(array | int | string $user = '');

    public static function getLoginControl(): ?Control;

    public static function getPasswordResetControl(): ?Control;

    public static function getSettingsControl(): ?Control;

    public static function isCLICompatible(): bool;

    /**
     * Authenticate the user
     *
     * @throws Exception
     */
    public function auth(string | array | int $authParams);

    public function getUser(): \QUI\Interfaces\Users\User;

    public function getTitle(null | Locale $Locale = null): string;

    public function getDescription(null | Locale $Locale = null): string;

    /**
     * CLI
     */

    public function getUserId(): bool | int;

    public function getUserUUID(): string;

    /**
     * The CLI Authentication, only if isCLICompatible returns true
     */
    public function cliAuthentication(Console $Console);

    /**
     * @return bool - true, if the authenticator can be used as primary authentication
     */
    public function isPrimaryAuthentication(): bool;

    /**
     * @return bool - true, if the authenticator can be used as primary authentication
     */
    public function isSecondaryAuthentication(): bool;
}
