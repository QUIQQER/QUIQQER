<?php

/**
 * This file contains QUI\Users\Auth\QUIQQER
 */

namespace QUI\Users\Auth;

use QUI;
use QUI\Users\AbstractAuthenticator;
use QUI\Utils\Security\Orthos;

/**
 * Class Auth
 * Standard QUIQQER Authentication
 *
 * @package QUI\Users
 */
class QUIQQER extends AbstractAuthenticator
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
     * @var bool
     */
    protected $authenticated = false;

    /**
     * @param string $username
     * @throws QUI\Exception
     */
    public function __construct($username = '')
    {
        $username = Orthos::clear($username);

        if (\function_exists('get_magic_quotes_gpc') && !\get_magic_quotes_gpc()) {
            $username = \addslashes($username);
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
        if (\is_null($Locale)) {
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
        if (\is_null($Locale)) {
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
        if (!\is_string($this->username) || empty($this->username)) {
            throw new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail.wrong.username.input'],
                401
            );
        }

        if (\is_array($password) && isset($password['password'])) {
            $password = $password['password'];
        }

        if (empty($password)) {
            throw new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail.wrong.password.input'],
                401
            );
        }

        if (!\is_string($password) || empty($password)) {
            throw new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail.wrong.password.input'],
                401
            );
        }

        $userData = QUI::getDataBase()->fetch([
            'select' => ['password'],
            'from'   => QUI::getUsers()->table(),
            'where'  => [
                'id' => $this->getUserId()
            ],
            'limit'  => 1
        ]);

        if (empty($userData)
            || !isset($userData[0]['password'])
            || empty($userData[0]['password'])
        ) {
            throw new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail'],
                401
            );
        }

        // get password hash from db
        $passwordHash = $userData[0]['password'];

        // generate password with given password and salt
        if (!\password_verify($password, $passwordHash)) {
            // fallback to old method
            $salt               = \mb_substr($passwordHash, 0, SALT_LENGTH);
            $actualPasswordHash = $this->genHash($password, $salt);

            if ($actualPasswordHash !== $passwordHash) {
                throw new QUI\Users\Exception(
                    ['quiqqer/system', 'exception.login.fail'],
                    401
                );
            }

            QUI::getDataBase()->update(
                QUI::getDBTableName('users'),
                ['password' => QUI\Security\Password::generateHash($password)],
                ['id' => $this->getUserId()]
            );
        }

        $this->authenticated = true;

        return true;
    }

    /**
     * Old genHash method
     *
     * @param string $pass
     * @param string $salt
     * @return string
     * @deprecated
     */
    protected function genHash($pass, $salt = null)
    {
        if ($salt === null) {
            $randomBytes = \openssl_random_pseudo_bytes(SALT_LENGTH);
            $salt        = \mb_substr(\bin2hex($randomBytes), 0, SALT_LENGTH);
        }

        return $salt.\md5($salt.$pass);
    }

    /**
     * Return the user object
     *
     * @return \QUI\Interfaces\Users\User
     * @throws QUI\Users\Exception
     */
    public function getUser()
    {
        if (!\is_null($this->User)) {
            return $this->User;
        }

        $User = false;

        if (QUI::conf('globals', 'emaillogin') && \strpos($this->username, '@') !== false) {
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
                    ['quiqqer/system', 'exception.login.fail.user.not.found'],
                    404
                );
            }
        }

        $this->User = $User;

        return $this->User;
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
     * @return bool
     */
    public static function isCLICompatible()
    {
        return true;
    }

    /**
     * @param QUI\System\Console $Console
     * @throws QUI\Exception
     */
    public function cliAuthentication(QUI\System\Console $Console)
    {
        $username = $Console->getArgument('username');
        $password = $Console->getArgument('password');

        if (empty($username)) {
            $Console->writeLn("Please enter your username");
            $Console->writeLn("Username: ", 'green');

            $Console->setArgument('username', $Console->readInput());
            $username = $Console->getArgument('username');
        }

        if (empty($password)) {
            $Console->clearMsg();
            $Console->writeLn("Please enter your password");
            $Console->writeLn("Password: ", 'green');
            $Console->clearMsg();

            $Console->setArgument('password', QUI\Utils\System\Console::readPassword());
            $password = $Console->getArgument('password');
        }

        $this->username = $username;
        $this->auth($password);
    }
}
