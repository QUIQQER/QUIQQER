<?php

/**
 * This file contains \QUI\Users\AuthenticatorInterface
 */

namespace QUI\Users;

use QUI\Control;
use QUI\Interfaces\Users\User;
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
     * @param array|int|string|User|null $user - name of the user, or user id
     */
    public function __construct(null | array | int | string | User $user = null);

    public static function getLoginControl(): ?Control;

    public function getPasswordResetControl(): ?Control;

    public function getSettingsControl(): ?Control;

    public static function isCLICompatible(): bool;

    /**
     * Authenticate the user
     *
     * @throws Exception
     */
    public function auth(string | array | int $authParams);

    public function getUser(): User;

    public function getTitle(null | Locale $Locale = null): string;

    public function getDescription(null | Locale $Locale = null): string;

    public function getFrontendTitle(null | Locale $Locale = null): string;

    public function getFrontendDescription(null | Locale $Locale = null): string;

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
