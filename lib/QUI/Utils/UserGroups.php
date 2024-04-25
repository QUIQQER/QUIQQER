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
    /**
     * @param array $array
     * @return string
     */
    public static function parseUGArrayToString($array)
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

    /**
     * Return the user group string from an user
     *
     * @param QUI\Interfaces\Users\User $User
     * @return string
     */
    public static function getUserGroupStringFromUser(QUI\Interfaces\Users\User $User)
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
     *
     * @param QUI\Interfaces\Users\User $User
     * @param $ugString
     * @return bool
     */
    public static function isUserInUserGroupString(QUI\Interfaces\Users\User $User, $ugString)
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
     *
     * @param string $str
     * @return array
     */
    public static function parseUsersGroupsString($str)
    {
        $result = [
            'users' => [],
            'groups' => []
        ];

        if (!\is_string($str)) {
            return $result;
        }

        if (empty($str)) {
            $ugs = [];
        } else {
            $ugs = explode(',', $str);
        }

        foreach ($ugs as $ug) {
            if (\strpos($ug, 'g') !== false) {
                $result['groups'][] = (int)\substr($ug, 1);
                continue;
            }

            if (\strpos($ug, 'u') !== false) {
                $result['users'][] = (int)\substr($ug, 1);
            }
        }

        return $result;
    }

    /**
     * is the string an UG-String
     *
     * @param string $ugString
     * @return bool
     */
    public static function isUserGroupString($ugString)
    {
        $ugString = explode(',', $ugString);

        foreach ($ugString as $entry) {
            if (\strpos($entry, 'g') === false && \strpos($entry, 'u') === false) {
                return false;
            }
        }

        return true;
    }
}
