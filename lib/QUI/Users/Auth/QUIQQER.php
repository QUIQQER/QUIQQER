<?php

/**
 * This file contains QUI\Users\Auth\QUIQQER
 */
namespace QUI\Users\Auth;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * Class Auth
 * Standard QUIQQER Authentication
 *
 * @package QUI\Users
 */
class QUIQQER implements QUI\Users\AuthInterface
{
    /**
     * User object
     * @var false|QUI\Users\User
     */
    protected $User = null;

    /**
     * Name of the user
     * @var string
     */
    protected $username = null;

    /**
     * @param string $username
     * @throws QUI\Exception
     */
    public function __construct($username = '')
    {
        if (!is_string($username) || empty($username)) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.wrong.username.input'),
                401
            );
        }

        $username = Orthos::clear($username);

        if (function_exists('get_magic_quotes_gpc') && !get_magic_quotes_gpc()) {
            $username = addslashes($username);
        }

        $this->username = $username;
    }

    /**
     * Return the auth title
     *
     * @param null|\QUI\Locale $Locale
     * @return string
     */
    public function getTitle($Locale = null)
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.auth.title');
    }

    /**
     * Return the auth title
     *
     * @param null|\QUI\Locale $Locale
     * @return string
     */
    public function getDescription($Locale = null)
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.auth.description');
    }

    /**
     * Authenticate the user
     *
     * @param string $password
     * @return boolean
     *
     * @throws QUI\Exception
     */
    public function auth($password)
    {
        if (is_array($password) && isset($password['password'])) {
            $password = $password['password'];
        }

        if (empty($password)) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.wrong.password.input'),
                401
            );
        }

        if (!is_string($password) || empty($password)) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.wrong.password.input'),
                401
            );
        }

        $userData = QUI::getDataBase()->fetch(array(
            'select' => array('password'),
            'from'   => QUI::getUsers()->table(),
            'where'  => array(
                'id' => $this->getUserId()
            ),
            'limit'  => 1
        ));

        if (empty($userData)
            || !isset($userData[0]['password'])
            || empty($userData[0]['password'])
        ) {
            return false;
        }

        // retrieve salt from saved password
        $savedPassword = $userData[0]['password'];
        $salt          = mb_substr($savedPassword, 0, SALT_LENGTH);

        // generate password with given password and salt
        $password = QUI::getUsers()->genHash($password, $salt);

        if ($savedPassword !== $password) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail'),
                401
            );
        }

        return true;
    }

    /**
     * Return the user object
     *
     * @return \QUI\Interfaces\Users\User
     * @throws QUI\Users\Exception
     */
    public function getUser()
    {
        if (!is_null($this->User)) {
            return $this->User;
        }

        $User = false;

        if (QUI::conf('globals', 'emaillogin')
            && strpos($this->username, '@') !== false
        ) {
            try {
                $User = QUI::getUsers()->getUserByMail($this->username);
            } catch (QUI\Exception $Exception) {
            }
        }

        if ($User === false) {
            try {
                $User = QUI::getUsers()->getUserByName($this->username);
            } catch (QUI\Exception $Exception) {
                throw new QUI\Users\Exception(
                    array('quiqqer/system', 'exception.login.fail.user.not.found'),
                    404
                );
            }
        }

        $this->User = $User;

        return $this->User;
    }

    /**
     * Return the ID of the user
     *
     * @return integer
     * @throws QUI\Users\Exception
     */
    public function getUserId()
    {
        return $this->getUser()->getId();
    }

    /**
     * Controls
     */

    /**
     * @return Controls\QUIQQERLogin
     */
    public static function getLoginControl()
    {
        return new Controls\QUIQQERLogin();
    }

    /**
     * @return null
     */
    public static function getPasswordResetControl()
    {
        return null;
    }

    /**
     * @return null
     */
    public static function getSettingsControl()
    {
        return null;
    }

    /**
     * @return null
     */
    public static function getRegisterControl()
    {
        return null;
    }
}
