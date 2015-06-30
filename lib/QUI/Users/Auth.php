<?php

/**
 * This file contains QUI\Users\Auth
 */
namespace QUI\Users;

use QUI;

/**
 * Class Auth
 * Standard QUIQQER authentification
 *
 * @package QUI\Users
 */
class Auth implements QUI\Interfaces\Users\Auth
{
    /**
     * User object
     * @var false|QUI\Users\User
     */
    protected $_User;

    /**
     * Name of the user
     * @var string
     */
    protected $_username;

    /**
     * @param string $username
     */
    public function __construct($username = '')
    {
        $this->_username = $username;
    }

    /**
     * Authenticate the user
     *
     * @param string $password
     * @return boolean
     */
    public function auth($password = '')
    {
        $userData = QUI::getDataBase()->fetch(array(
            'select' => array('id', 'password'),
            'from'   => QUI::getUsers()->Table(),
            'where'  => array(
                'id'       => $this->getUserId(),
                'password' => QUI::getUsers()->genHash($password)
            ),
            'limit'  => 1
        ));

        return isset($userData[0]);
    }

    /**
     * Return the quiqqer user id
     *
     * @return integer|boolean
     */
    public function getUserId()
    {
        if ($this->_User) {
            return $this->_User->getId();
        }

        $username = $this->_username;
        $User = false;

        /**
         * Standard Authentifizierung
         */
        if (QUI::conf('globals', 'emaillogin')
            && strpos($username, '@') !== false
        ) {
            try {
                $User = QUI::getUsers()->getUserByMail($username);
            } catch (QUI\Exception $Exception) {

            }
        }

        if ($User === false) {
            try {
                $User = QUI::getUsers()->getUserByName($username);
            } catch (QUI\Exception $Exception) {
                return false;
            }
        }

        $this->_User = $User;

        return $User->getId();
    }
}