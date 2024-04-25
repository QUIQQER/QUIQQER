<?php

/**
 * This file contains \QUI\Interfaces\Users\User
 */

namespace QUI\Interfaces\Users;

use QUI\Countries\Country;
use QUI\Exception;
use QUI\Groups\Group;
use QUI\Locale;
use QUI\Projects\Media\Image;
use QUI\Users\Address;

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
     *
     * @return boolean
     */
    public function isSU(): bool;

    /**
     * @param integer|string $groupId
     * @return boolean
     */
    public function isInGroup(int|string $groupId): bool;

    /**
     * the user can use the backend?
     *
     * @return bool
     */
    public function canUseBackend(): bool;

    /**
     * Logout the user
     */
    public function logout();

    /**
     * Activate the user
     *
     * @param string $code - activation code [optional]
     */
    public function activate(string $code, ?User $PermissionUser = null);

    /**
     * Deactivate the user
     */
    public function deactivate(?User $PermissionUser = null);

    /**
     * Disable a user
     * The user data will be lost, but the user still exist
     *
     * @param User|null $PermissionUser
     */
    public function disable(?User $PermissionUser = null);

    /**
     * Save all attributes of the user
     *
     * @param User|null $PermissionUser
     */
    public function save(?User $PermissionUser = null);

    /**
     * Delete the user
     */
    public function delete(?User $PermissionUser = null);

    /**
     * Returns the user id
     *
     * @return int|false
     * @deprecated
     */
    public function getId(): int|false;

    /**
     * alias for getUUID
     *
     * @return string|int
     * @deprecated use getUUID
     */
    public function getUniqueId(): string|int;

    /**
     * Returns the user uuid
     *
     * @return string|int
     */
    public function getUUID(): string|int;

    /**
     * Returns the name of the user
     * If the user has an first and Lastname, it returns the "Firstname Lastname".
     * otherwise it returns getUsername()
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the username
     *
     * @return string
     */
    public function getUsername(): string;

    /**
     * Return the user language
     *
     * @return string
     */
    public function getLang(): string;

    /**
     * Returns the Locale object depending on the user
     *
     * @return Locale
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
     *
     * @return boolean
     */
    public function getStatus(): bool;

    /**
     * Has the user the right?
     *
     * @param string $right
     * @param boolean|array $ruleset - (optional), you can specify a ruleset, a rules = array with rights
     *
     * @return mixed
     */
    public function getPermission(string $right, bool|array $ruleset = false): mixed;

    /**
     * set a group to the user
     *
     * @param array|string $groups
     */
    public function setGroups(array|string $groups);

    /**
     * Returns all groups in which the user is
     *
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     *
     * @return array
     */
    public function getGroups(bool $array = true): array;

    /**
     * Get an address from the user
     *
     * @param integer $id - ID of the address
     * @return Address
     *
     * @throws Exception
     */
    public function getAddress(int $id): Address;

    /**
     * Return the Country from the user
     *
     * @return Country|boolean
     */
    public function getCountry(): Country|bool;

    /**
     * Returns the avatar of the user
     *
     * @return Image|false
     */
    public function getAvatar(): Image|bool;

    /**
     * Set the password of the user
     *
     * @param string $new - new password
     * @param \QUI\Users\User|boolean $ParentUser
     */
    public function setPassword($new, $ParentUser = false);

    /**
     * Checks the password if it's the user from
     *
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     */
    public function checkPassword(string $pass, bool $encrypted = false);

    /**
     * Is the user deleted?
     *
     * @return boolean
     */
    public function isDeleted(): bool;

    /**
     * is the user active?
     *
     * @return boolean
     */
    public function isActive(): bool;

    /**
     * is the user online at the moment?
     *
     * @return boolean
     */
    public function isOnline(): bool;

    /**
     * Is the user a company?
     *
     * @return mixed
     */
    public function isCompany(): mixed;

    /**
     * Set the company status, whether the user is a company or not
     *
     * @param boolean $status - true or false
     */
    public function setCompanyStatus(bool $status);

    /**
     * Add the user to a group
     *
     * @param integer $groupId
     */
    public function addToGroup(int $groupId);

    /**
     * Remove a group from the user
     *
     * @param integer|Group $Group
     */
    public function removeGroup(Group|int $Group);

    /**
     * refresh the data from the database
     *
     * @throws \QUI\Users\Exception
     */
    public function refresh();

    // region qdom

    /**
     * Remove an attribute
     *
     * @param string $key
     */
    public function removeAttribute($key);

    /**
     * Set an attribute of the user
     *
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute($key, $value);

    /**
     * set multiple attributes
     *
     * @param array $attributes
     */
    public function setAttributes($attributes);

    /**
     * Get an attribute of the user
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name);

    /**
     * Return all attributes
     *
     * @return array
     */
    public function getAttributes();

    //endregion
}
