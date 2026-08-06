<?php

/**
 * This file contains \QUI\Interfaces\Users\User
 */

namespace QUI\Interfaces\Users;

use QUI\Countries\Country;
use QUI\Exception;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User as QUIUserInterface;
use QUI\Locale;
use QUI\Projects\Media\Image;
use QUI\Users\Address;
use QUI\Users\AuthenticatorInterface;

/**
 * The user interface
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
interface User
{
    /**
     * Is the user superuser?
     */
    public function isSU(): bool;

    public function isInGroup(int | string $groupId): bool;

    public function canUseBackend(): bool;

    public function logout(): void;

    /**
     * Activate the user
     *
     * @param string $code - activation code
     */
    public function activate(string $code = '', null | User $PermissionUser = null): bool | int;

    public function deactivate(null | User $PermissionUser = null): bool;

    /**
     * Disable a user
     * The user data will be lost, but the user still exist
     */
    public function disable(null | User $PermissionUser = null): bool;

    /**
     * Save all attributes of the user
     */
    public function save(null | User $PermissionUser = null): void;

    public function delete(null | User $PermissionUser = null): bool;

    /**
     * @deprecated
     */
    public function getId(): int | false;

    /**
     * @deprecated use getUUID
     */
    public function getUniqueId(): string | int;

    public function getUUID(): string | int;

    /**
     * Returns the name of the user
     * If the user has a first and Lastname, it returns the "Firstname Lastname".
     * otherwise it returns getUsername()
     */
    public function getName(): string;

    public function getUsername(): string;

    /**
     * Return the user language
     */
    public function getLang(): string;

    /**
     * Returns the Locale object depending on the user
     */
    public function getLocale(): Locale;

    /**
     * Returns the class type
     *
     * @return string (\QUI\Users\Nobody|\QUI\Users\SystemUser|\QUI\Users\User)
     */
    public function getType(): string;

    /**
     * Returns the active status of the user
     * is the user active or not?
     */
    public function getStatus(): int;

    /**
     * Has the user the right?
     *
     * @param string $right
     * @param callable|bool|string $ruleset - (optional), you can specify a ruleset, a rules = array with rights
     *
     * @return mixed
     */
    public function getPermission(
        string $right,
        callable | bool | string $ruleset = false
    ): mixed;

    /**
     * @param array<array-key, mixed>|string $groups
     *
     * @return bool|void
     */
    public function setGroups(array | string $groups);

    /**
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     *
     * @return ($array is true ? array<int, Group> : array<int, string>)
     */
    public function getGroups(bool $array = true): array;

    public function getCountry(): null | Country;

    public function getAvatar(): Image | null;

    /**
     * @return bool|void
     */
    public function setPassword(string $new, null | User $PermissionUser = null);

    public function changePassword(
        string $newPassword,
        string $oldPassword,
        null | QUIUserInterface $ParentUser = null
    ): void;

    /**
     * Checks the password if it's the user from
     *
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     *
     * @return bool
     */
    public function checkPassword(string $pass, bool $encrypted = false);

    public function isDeleted(): bool;

    public function isActive(): bool;

    public function isOnline(): bool;

    public function isCompany(): mixed;

    /**
     * @return void
     */
    public function setCompanyStatus(bool $status);

    /**
     * @return void
     */
    public function addToGroup(int | string $groupId);

    /**
     * @return void
     */
    public function removeGroup(Group | int | string $Group);

    /**
     * @return void
     *
     * @throws \QUI\Users\Exception
     */
    public function refresh();

    // region qdom
    /**
     * @return void
     */
    public function removeAttribute(string $key);

    /**
     * @return void
     */
    public function setAttribute(string $key, mixed $value);

    /**
     * @param array<string, mixed> $attributes
     *
     * @return void
     */
    public function setAttributes(array $attributes);

    public function getAttribute(string $name): mixed;

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array;
    //endregion

    //region authenticator

    /**
     * @param string $authenticator
     * @return AuthenticatorInterface
     *
     * @throws \QUI\Users\Exception
     */
    public function getAuthenticator(string $authenticator): AuthenticatorInterface;

    /**
     * @return array<array-key, AuthenticatorInterface>
     */
    public function getAuthenticators(): array;

    /**
     * @throws \QUI\Users\Exception
     */
    public function disableAuthenticator(string $authenticator, null | QUIUserInterface $ParentUser = null): void;

    /**
     * @throws \QUI\Users\Exception
     */
    public function enableAuthenticator(string $authenticator, null | QUIUserInterface $ParentUser = null): void;

    public function hasAuthenticator(string $authenticator): bool;
    //endregion

    //region addresses

    /**
     * @param array<string, mixed> $params
     * @param User|null $ParentUser
     * @return ?Address
     *
     * @throws Exception
     */
    public function addAddress(array $params = [], null | QUIUserInterface $ParentUser = null): ?Address;

    /**
     * @throws Exception
     */
    public function getAddress(int | string $id): Address;

    public function getStandardAddress(): null | Address;

    /**
     * @return array<string, Address>
     */
    public function getAddressList(): array;
    //endregion
}
