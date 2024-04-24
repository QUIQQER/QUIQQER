<?php

/**
 * This file contains QUI\Users\Auth\QUIQQER
 */

namespace QUI\Users\Auth;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Users\AbstractAuthenticator;
use QUI\Users\Exception;
use QUI\Utils\Security\Orthos;

use function bin2hex;
use function is_array;
use function is_null;
use function is_string;
use function mb_substr;
use function md5;
use function openssl_random_pseudo_bytes;
use function password_verify;
use function trim;

/**
 * Class Auth
 * Standard QUIQQER Authentication
 */
class QUIQQER extends AbstractAuthenticator
{
    /**
     * User object
     * @var ?User
     */
    protected ?User $User = null;

    /**
     * Name of the user
     * @var string|null
     */
    protected ?string $username = null;

    /**
     * @var bool
     */
    protected bool $authenticated = false;

    /**
     * @param array|int|string $user
     */
    public function __construct(array|int|string $user = '')
    {
        $user = Orthos::clear($user);
        $this->username = $user;
    }

    /**
     * @return Controls\QUIQQERLogin
     */
    public static function getLoginControl(): Controls\QUIQQERLogin
    {
        return new Controls\QUIQQERLogin();
    }

    /**
     * @return bool
     */
    public static function isCLICompatible(): bool
    {
        return true;
    }

    /**
     * Return the auth title
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getTitle(Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.auth.title');
    }

    /**
     * Return the auth title
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.auth.description');
    }

    /**
     * Return the user object
     *
     * @return User
     * @throws Exception
     */
    public function getUser(): User
    {
        if (!is_null($this->User)) {
            return $this->User;
        }

        $User = false;

        if (QUI::conf('globals', 'emaillogin') && str_contains($this->username, '@')) {
            try {
                $User = QUI::getUsers()->getUserByMail($this->username);
            } catch (QUI\Exception) {
            }
        }

        if ($User === false) {
            try {
                $User = QUI::getUsers()->getUserByName($this->username);
            } catch (QUI\Exception) {
                throw new QUI\Users\Exception(
                    ['quiqqer/quiqqer', 'exception.login.fail.user.not.found'],
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
     * @param QUI\System\Console $Console
     * @throws QUI\Exception
     */
    public function cliAuthentication(QUI\System\Console $Console): void
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

    /**
     * Authenticate the user
     *
     * @param string|int|array $authParams
     * @return boolean
     *
     * @throws Exception
     * @throws QUI\Database\Exception
     */
    public function auth(string|int|array $authParams): bool
    {
        if (!is_string($this->username) || empty($this->username)) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.login.fail.wrong.username.input'],
                401
            );
        }

        if (\is_array($password) && isset($password['password'])) {
            $password = $password['password'];
        }

        if (!\is_string($password) || empty($password)) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.login.fail.wrong.password.input'],
                401
            );
        }

        $password = \trim($password);

        $userData = QUI::getDataBase()->fetch([
            'select' => ['password'],
            'from' => QUI::getUsers()->table(),
            'where' => [
                'uuid' => $this->getUserUUID()
            ],
            'limit' => 1
        ]);

        if (empty($userData) || empty($userData[0]['password'])) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.login.fail'],
                401
            );
        }

        // get password hash from db
        $passwordHash = $userData[0]['password'];

        // generate password with given password and salt
        if (!\password_verify($password, $passwordHash)) {
            // fallback to old method
            $salt = \mb_substr($passwordHash, 0, SALT_LENGTH);
            $actualPasswordHash = $this->genHash($password, $salt);

            if ($actualPasswordHash !== $passwordHash) {
                throw new QUI\Users\Exception(
                    ['quiqqer/quiqqer', 'exception.login.fail'],
                    401
                );
            }

            QUI::getDataBase()->update(
                QUI::getDBTableName('users'),
                ['password' => QUI\Security\Password::generateHash($password)],
                ['uuid' => $this->getUserUUID()]
            );
        }

        $this->authenticated = true;

        return true;
    }

    /**
     * Old genHash method
     *
     * @param string $pass
     * @param string|null $salt
     * @return string
     * @deprecated
     */
    protected function genHash(string $pass, string $salt = null): string
    {
        if ($salt === null) {
            $randomBytes = openssl_random_pseudo_bytes(SALT_LENGTH);
            $salt = mb_substr(bin2hex($randomBytes), 0, SALT_LENGTH);
        }

        return $salt . md5($salt . $pass);
    }
}
