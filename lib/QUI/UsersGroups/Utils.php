<?php

/**
 * This file contains \QUI\UsersGroups\Utils
 */

namespace QUI\UsersGroups;

use QUI;

/**
 * Helper for users groups controls
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package quiqqer/quiqqer
 */
class Utils
{
    /**
     * Return an array (array('users', 'groups') from a user_groups string eq: u796832571,g654240634
     *
     * @param $str
     *
     * @return array
     */
    static function parseUsersGroupsString($str)
    {
        $result = array(
            'users'  => array(),
            'groups' => array()
        );

        if (!is_string($str)) {
            return $result;
        }

        if (empty($str)) {
            $ugs = array();
        } else {
            $ugs = explode(',', $str);
        }

        foreach ($ugs as $ug) {

            if (strpos($ug, 'g') !== false) {
                $result['groups'][] = (int)substr($ug, 1);
                continue;
            }

            if (strpos($ug, 'u') !== false) {
                $result['users'][] = (int)substr($ug, 1);
            }
        }

        return $result;
    }
}
