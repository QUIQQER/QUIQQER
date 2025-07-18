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

class QUIQQER extends AbstractAuthenticator
{
    protected ?User $User = null;

    protected ?string $username = null;

    protected bool $authenticated = false;

    public function __construct(array | int | string $user = '')
    {
        $user = Orthos::clear($user);
        $this->username = $user;
    }

    public static function getLoginControl(): QUI\Control
    {
        return new Controls\QUIQQERLogin();
    }

    public static function isCLICompatible(): bool
    {
        return true;
    }

    public function getTitle(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.auth.title');
    }

    public function getDescription(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.auth.description');
    }

    /**
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
                    ['quiqqer/core', 'exception.login.fail.user.not.found'],
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
     * @throws Exception
     * @throws QUI\Database\Exception
     */
    public function auth(string | int | array $authParams): bool
    {
        if (!is_string($this->username) || empty($this->username)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.wrong.username.input'],
                401
            );
        }

        if (is_array($authParams) && isset($authParams['password'])) {
            $authParams = $authParams['password'];
        }

        if (!is_string($authParams) || empty($authParams)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.wrong.password.input'],
                401
            );
        }

        $authParams = trim($authParams);

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
                ['quiqqer/core', 'exception.login.fail'],
                401
            );
        }

        // get password hash from db
        $passwordHash = $userData[0]['password'];

        // generate password with given password and salt
        if (!password_verify($authParams, $passwordHash)) {
            // fallback to old method
            $salt = mb_substr($passwordHash, 0, SALT_LENGTH);
            $actualPasswordHash = $this->genHash($authParams, $salt);

            if ($actualPasswordHash !== $passwordHash) {
                throw new QUI\Users\Exception(
                    ['quiqqer/core', 'exception.login.fail'],
                    401
                );
            }

            QUI::getDataBase()->update(
                QUI::getDBTableName('users'),
                ['password' => QUI\Security\Password::generateHash($authParams)],
                ['uuid' => $this->getUserUUID()]
            );
        }

        $this->authenticated = true;

        return true;
    }

    /**
     * Old genHash method
     *
     * @deprecated
     */
    protected function genHash(string $pass, null | string $salt = null): string
    {
        if ($salt === null) {
            $randomBytes = openssl_random_pseudo_bytes(SALT_LENGTH);
            $salt = mb_substr(bin2hex($randomBytes), 0, SALT_LENGTH);
        }

        return $salt . md5($salt . $pass);
    }
}
