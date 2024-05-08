<?php

/**
 * This file contains \QUI\Utils\UserGroups
 */

namespace QUI\Utils;

use QUI;

use function array_flip;
use function explode;
use function implode;
use function is_string;
use function substr;

/**
 * Helper for users group strings
 * UG-Strings = u19939939,g9929299299,g999929929292,u882828282
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class UserGroups
{
    public static function parseUGArrayToString(array $array): string
    {
        $result = '';

        if (!isset($array['users'])) {
            return $result;
        }


        if (!isset($array['groups'])) {
            return $result;
        }

        $list = [];

        foreach ($array['users'] as $uid) {
            $list[] = 'u' . $uid;
        }

        foreach ($array['groups'] as $gid) {
            $list[] = 'g' . $gid;
        }

        return implode(',', $list);
    }

    public static function getUserGroupStringFromUser(QUI\Interfaces\Users\User $User): string
    {
        $result = [];
        $result[] = 'u' . $User->getUUID();

        $groups = $User->getGroups();

        /* @var $Group QUI\Groups\Group */
        foreach ($groups as $Group) {
            $result[] = 'g' . $Group->getUUID();
        }

        return implode(',', $result);
    }

    /**
     * Check user in the user group string
     * there are also groups of user tested
     */
    public static function isUserInUserGroupString(QUI\Interfaces\Users\User $User, $ugString): bool
    {
        if (!is_string($ugString)) {
            return false;
        }

        $ugString = self::parseUsersGroupsString($ugString);
        $users = $ugString['users'];
        $groups = $ugString['groups'];

        foreach ($users as $uid) {
            if ($uid == $User->getUUID()) {
                return true;
            }
        }

        $userGroups = array_flip($User->getGroups(false));

        foreach ($groups as $gid) {
            if (isset($userGroups[$gid])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return an array (array('users', 'groups') from a user_groups string eq: u796832571,g654240634
     */
    public static function parseUsersGroupsString(string $str): array
    {
        $result = [
            'users' => [],
            'groups' => []
        ];

        if (empty($str)) {
            $ugs = [];
        } else {
            $ugs = explode(',', $str);
        }

        foreach ($ugs as $ug) {
            if (str_contains($ug, 'g')) {
                $result['groups'][] = (int)substr($ug, 1);
                continue;
            }

            if (str_contains($ug, 'u')) {
                $result['users'][] = substr($ug, 1);
            }
        }

        return $result;
    }

    public static function isUserGroupString(string $ugString): bool
    {
        $ugString = explode(',', $ugString);

        foreach ($ugString as $entry) {
            if (str_contains($entry, 'g')) {
                continue;
            }

            if (str_contains($entry, 'u')) {
                continue;
            }

            return false;
        }

        return true;
    }
}
