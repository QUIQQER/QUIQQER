<?php

/**
 * This file contains \QUI\Users\Manager
 */

namespace QUI\Users;

use DOMElement;
use DOMXPath;
use PDO;
use PDOException;
use QUI;
use QUI\Database\Exception;
use QUI\ExceptionStack;
use QUI\Interfaces\Users\User as QUIUserInterface;
use QUI\Security\Password;
use QUI\Utils\DOM;
use QUI\Utils\Security\Orthos;
use QUI\Utils\Text\XML;
use Throwable;

use function class_implements;
use function defined;
use function explode;
use function file_exists;
use function func_get_args;
use function func_num_args;
use function implode;
use function in_array;
use function is_numeric;
use function is_object;
use function md5;
use function microtime;
use function preg_replace;
use function print_r;
use function round;
use function serialize;
use function str_contains;
use function strtotime;
use function substr;
use function time;

/**
 * QUIQQER user manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event onUserLogin [ \QUI\Users\User ]
 */
class Manager
{
    const AUTH_ERROR_AUTH_ERROR = 'AUTH_ERROR_AUTH_ERROR';

    const AUTH_ERROR_USER_NOT_FOUND = 'auth_error_user_not_found';

    const AUTH_ERROR_USER_NOT_ACTIVE = 'auth_error_user_not_active';

    const AUTH_ERROR_USER_DELETED = 'auth_error_user_deleted';

    const AUTH_ERROR_LOGIN_EXPIRED = 'auth_error_login_expired';

    const AUTH_ERROR_NO_ACTIVE_GROUP = 'auth_error_no_active_group';

    /**
     * internal prevention for multiple session user calling
     */
    protected bool $multipleCallPrevention = false;

    /**
     * @var array - list of users (cache)
     */
    private array $users = [];

    private array $usersUUIDs = [];

    private ?Nobody $Nobody = null;

    private ?SystemUser $SystemUser = null;

    private ?QUIUserInterface $Session = null;

    /**
     * Return the db table for the addresses
     */
    public static function tableAddress(): string
    {
        return QUI::getDBTableName('users_address');
    }

    /**
     * @param string $pass
     * @param string|null $salt -> deprecated
     *
     * @return string
     * @deprecated
     *
     * Generates a hash of a password
     *
     */
    public static function genHash(string $pass, null | string $salt = null): string
    {
        return Password::generateHash($pass);
    }

    /**
     * Get user profile template (profile window)
     */
    public static function getProfileTemplate(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine(true);
        $packages = QUI::getPackageManager()->getInstalled();
        $extend = '';

        foreach ($packages as $package) {
            $name = $package['name'];
            $userXml = OPT_DIR . $name . '/user.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            $Document = XML::getDomFromXml($userXml);
            $Path = new DOMXPath($Document);

            $tabs = $Path->query("//user/profile/tab");

            /* @var $Tab DOMElement */
            foreach ($tabs as $Tab) {
                $extend .= DOM::parseCategoryToHTML($Tab);
            }
        }

        $Engine->assign([
            'QUI' => new QUI(),
            'extend' => $extend
        ]);

        return $Engine->fetch(SYS_DIR . 'template/users/profile.html');
    }

    /**
     * Create the database tables for the users
     *
     * @throws \Exception
     */
    public function setup(): void
    {
        // moved to migration v2
    }

    /**
     * Return the db table
     */
    public static function table(): string
    {
        return QUI::getDBTableName('users');
    }

    /**
     * Get the user by id or uuid
     *
     * @param int|string $id - Could be user-id or user uuid
     * @return QUI\Interfaces\Users\User
     *
     * @throws Exception
     * @throws QUI\Exception
     * @throws ExceptionStack
     */
    public function get(int | string $id): QUI\Interfaces\Users\User
    {
        if (is_numeric($id)) {
            $id = (int)$id;

            if (!$id) {
                return new Nobody();
            }

            if ($id == 5) {
                return new SystemUser();
            }
        }

        if (isset($this->usersUUIDs[$id])) {
            $id = $this->usersUUIDs[$id];
        }

        if (isset($this->users[$id])) {
            return $this->users[$id];
        }

        try {
            $User = new User($id);
        } catch (\Exception $exception) {
            try {
                $userGetResult = QUI::getEvents()->fireEvent('userGet', [$id]);

                if (!empty($userGetResult)) {
                    $UserInstance = null;

                    foreach ($userGetResult as $entry) {
                        if (!$entry) {
                            continue;
                        }

                        if (!in_array(QUI\Interfaces\Users\User::class, class_implements($entry))) {
                            continue;
                        }

                        $UserInstance = $entry;
                    }

                    if ($UserInstance && in_array(QUI\Interfaces\Users\User::class, class_implements($UserInstance))) {
                        $User = $UserInstance;
                    }
                }
            } catch (\Exception) {
            }

            if (empty($User)) {
                throw $exception;
            }
        }


        $uuid = $User->getUUID();

        if (QUI::isRuntimeCacheEnabled()) {
            $this->usersUUIDs[$User->getId()] = $User->getUUID();
            $this->users[$uuid] = $User;
        }

        return $User;
    }

    /**
     * Is the user authenticated
     *
     * @todo muss noch fremde nutzer prüfen
     */
    public function isAuth(QUIUserInterface $User): bool
    {
        if (!$User->getUUID()) {
            return false;
        }

        $_User = $this->getUserBySession();

        if ($User->getUUID() == $_User->getUUID()) {
            return true;
        }

        return false;
    }

    public function getUserBySession(): QUIUserInterface
    {
        if (defined('SYSTEM_INTERN')) {
            return $this->getSystemUser();
        }

        if ($this->Session !== null) {
            return $this->Session;
        }

        if ($this->multipleCallPrevention) {
            return $this->getNobody();
        }

        try {
            $result = QUI::getEvents()->fireEvent('userGetBySession');

            if (!empty($result)) {
                $UserInstance = null;

                foreach ($result as $entry) {
                    if (!$entry) {
                        continue;
                    }

                    if (!in_array(QUI\Interfaces\Users\User::class, class_implements($entry))) {
                        continue;
                    }

                    $UserInstance = $entry;
                }

                if ($UserInstance && in_array(QUI\Interfaces\Users\User::class, class_implements($UserInstance))) {
                    $this->Session = $UserInstance;
                    return $this->Session;
                }
            }
        } catch (\Exception $exception) {
            QUI\System\Log::addDebug($exception->getMessage());
        }

        $this->multipleCallPrevention = true;

        // max_life_time check
        try {
            $this->checkUserSession();
            $this->Session = $this->get(QUI::getSession()->get('uid'));
        } catch (QUI\Exception $Exception) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                QUI\System\Log::writeDebugException($Exception);
            }

            $this->Session = $this->getNobody();
        }

        return $this->Session;
    }

    public function getSystemUser(): SystemUser
    {
        if ($this->SystemUser === null) {
            $this->SystemUser = new SystemUser();
        }

        return $this->SystemUser;
    }

    public function getNobody(): Nobody
    {
        if ($this->Nobody === null) {
            $this->Nobody = new Nobody();
        }

        return $this->Nobody;
    }

    /**
     * Checks, if the session is ok
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function checkUserSession(): void
    {
        // max_life_time check
        $Session = QUI::getSession();

        $clearSessionData = static function () use ($Session): void {
            $sessionData = $Session->getSymfonySession()->all();

            foreach (array_keys($sessionData) as $key) {
                if (str_starts_with($key, 'auth-')) {
                    $Session->remove($key);
                }
            }
        };

        if (!$Session->check()) {
            $clearSessionData();

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.permission.session.expired'
                ),
                401
            );
        }

        if (!$Session->get('uid') || !$Session->get('auth')) {
            if (!$Session->get('inAuthentication')) {
                $clearSessionData();
            }

            if ($Session->get('expired.from.other')) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.session.expired.from.other'
                    ),
                    401
                );
            }

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.permission.session.expired'
                ),
                401
            );
        }

        try {
            $User = $this->get($Session->get('uid'));
        } catch (QUI\Exception $Exception) {
            $clearSessionData();
            throw $Exception;
        }

        if (!$User->isActive()) {
            $clearSessionData();

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.user.inactive'
                ),
                401
            );
        }

        // Mehrfachanmeldungen? Dann keine Prüfung
        if (
            QUI::conf('session', 'multible')
            || $Session->get('inAuthentication')
        ) {
            return;
        }

        $sessionSecHash = $Session->get('secHash');
        $secHash = $this->getSecHash();
        $userSecHash = $User->getAttribute('secHash');

        if ($sessionSecHash == $secHash && $userSecHash == $secHash) {
            return;
        }

        $message = $User->getLocale()->get(
            'quiqqer/core',
            'exception.session.expired.from.other'
        );

        $Session->set('uid', 0);
        $Session->getSymfonySession()->clear();
        $Session->refresh();
        $Session->set('expired.from.other', 1);

        throw new QUI\Users\Exception($message, 401);
    }

    /**
     * Generate a user-dependent security hash
     * There are different data use such as IP, User-Agent and the System-Salt
     *
     * @todo   noch eine eindeutige möglichkeit der Identifizierung des Browser finden
     */
    public function getSecHash(): string
    {
        $secHashData = [];
        $useragent = '';

        // chromeframe nicht mitaufnehmen -> bug
        if (
            isset($_SERVER['HTTP_USER_AGENT'])
            && !str_contains($_SERVER['HTTP_USER_AGENT'], 'chromeframe')
        ) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }

        $secHashData[] = $useragent;
        $secHashData[] = QUI\Utils\System::getClientIP();
        $secHashData[] = QUI::conf('globals', 'salt');

        return md5(serialize($secHashData));
    }

    /**
     * Is the Object a User?
     * It checks the user interface, for authentication please use ->isAuth()
     */
    public function isUser(mixed $User): bool
    {
        if (!is_object($User)) {
            return false;
        }

        if ($User instanceof User) {
            return true;
        }

        if ($User instanceof QUI\Interfaces\Users\User) {
            return true;
        }

        return false;
    }

    public function isSystemUser(mixed $User): bool
    {
        if (!is_object($User)) {
            return false;
        }

        if ($User::class === SystemUser::class) {
            return true;
        }

        return false;
    }

    /**
     * Create a new User
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function createChild(
        bool | string $username = false,
        null | QUIUserInterface $ParentUser = null
    ): QUIUserInterface {
        // check, is the user allowed to create new users
        QUI\Permissions\Permission::checkPermission(
            'quiqqer.admin.users.create',
            $ParentUser
        );

        if ($username) {
            if ($this->usernameExists($username)) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.lib.user.exist'
                    )
                );
            }

            $newName = $username;
        } else {
            $newUserLocale = QUI::getLocale()->get('quiqqer/core', 'user.create.new.username');
            $newName = $newUserLocale;

            while ($this->usernameExists($newName)) {
                $milliseconds = round(microtime(true) * 1000);
                $newName = $newUserLocale . ' (' . $milliseconds . ')';
            }
        }

        self::checkUsernameSigns($username);

        try {
            $uuid = QUI\Utils\Uuid::get();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Users\Exception('Could not create User. Please try again later.');
        }

        QUI::getDataBase()->insert(self::table(), [
            'uuid' => $uuid,
            'username' => $newName,
            'regdate' => time(),
            'lang' => QUI::getLocale()->getCurrent()
        ]);

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();
        $User = $this->get($newId);

        $Everyone = new QUI\Groups\Everyone();

        $User->setAttribute('toolbar', $Everyone->getAttribute('toolbar'));

        if (!$User->getAttribute('toolbar')) {
            $available = QUI\Editor\Manager::getToolbars();

            if (!empty($available)) {
                $User->setAttribute('toolbar', $available[0]);
            }
        }

        $User->addToGroup($Everyone->getUUID());
        $User->save($ParentUser);

        QUI::getEvents()->fireEvent('userCreate', [$User]);

        // workspace
        $this->setDefaultWorkspacesForUsers($User);

        return $User;
    }

    public function usernameExists(string $username): bool
    {
        if (empty($username)) {
            return false;
        }

        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'username',
                'from' => self::table(),
                'where' => [
                    'username' => $username
                ],
                'limit' => 1
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return false;
        }

        return isset($result[0]);
    }

    /**
     * Checks name for illegal characters
     *
     * @throws QUI\Users\Exception
     */
    public static function checkUsernameSigns(string $username): bool
    {
        if ($username !== self::clearUsername($username)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.lib.user.illegal.signs')
            );
        }

        return true;
    }

    /**
     * Delete illegal characters from the name
     */
    public static function clearUsername(string $username): string
    {
        return preg_replace('/[^a-zA-Z0-9-_äöüß@\.\+]/', '', $username);
    }

    /**
     * Set the default workspace for a user
     * The user must have administration permissions
     *
     * @throws QUI\Exception
     */
    public function setDefaultWorkspacesForUsers(QUIUserInterface $User): void
    {
        if (!QUI\Permissions\Permission::isAdmin($User)) {
            return;
        }

        $twoColumn = QUI\Workspace\Manager::getTwoColumnDefault();
        $threeColumn = QUI\Workspace\Manager::getThreeColumnDefault();

        $newWorkspaceId = QUI\Workspace\Manager::addWorkspace(
            $User,
            QUI::getLocale()->get('quiqqer/core', 'workspaces.2.columns'),
            $twoColumn,
            500,
            700
        );

        QUI\Workspace\Manager::addWorkspace(
            $User,
            QUI::getLocale()->get('quiqqer/core', 'workspaces.3.columns'),
            $threeColumn,
            500,
            700
        );

        QUI\Workspace\Manager::setStandardWorkspace($User, $newWorkspaceId);
    }

    /**
     * Create user with specific attributes
     *
     * @throws Exception
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     * @throws QUI\Permissions\Exception
     */
    public function createChildWithAttributes(
        array $attributes = [],
        ?QUIUserInterface $PermissionUser = null
    ): QUIUserInterface {
        // check, is the user allowed to create new users
        QUI\Permissions\Permission::checkPermission(
            'quiqqer.admin.users.create',
            $PermissionUser
        );

        $insertData = [
            'regdate' => time(),
            'lang' => QUI::getLocale()->getCurrent()
        ];

        // Specific attributes that identify users need to checked first
        if (!empty($attributes['username'])) {
            $username = $attributes['username'];

            if ($this->usernameExists($username)) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.lib.user.exist'
                    )
                );
            }

            self::checkUsernameSigns($username);

            $insertData['username'] = $username;
            unset($attributes['username']);
        } else {
            $newUserLocale = QUI::getLocale()->get('quiqqer/core', 'user.create.new.username');
            $newName = $newUserLocale;

            while ($this->usernameExists($newName)) {
                $milliseconds = round(microtime(true) * 1000);
                $newName = $newUserLocale . ' (' . $milliseconds . ')';
            }

            $insertData['username'] = $newName;
        }

        if (!empty($attributes['uuid'])) {
            $uuid = $attributes['uuid'];

            try {
                $this->get($uuid);

                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.lib.user.exist'
                    )
                );
            } catch (\Exception) {
                // uuid does not exist - this is good
            }

            $insertData['uuid'] = $uuid;
            unset($attributes['uuid']);
        } else {
            try {
                $insertData['uuid'] = QUI\Utils\Uuid::get();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                throw new QUI\Users\Exception('Could not create User. Please try again later.');
            }
        }

        if (!empty($attributes['id'])) {
            $id = (int)$attributes['id'];

            try {
                $this->get($id);

                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.lib.user.exist'
                    )
                );
            } catch (\Exception) {
                // id does not exist - this is good
            }

            $insertData['id'] = $id;
            unset($attributes['id']);
        }

        QUI::getDataBase()->insert(self::table(), $insertData);

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();
        $User = $this->get($newId);

        $Everyone = new QUI\Groups\Everyone();

        $User->setAttribute('toolbar', $Everyone->getAttribute('toolbar'));

        if (!$User->getAttribute('toolbar')) {
            $available = QUI\Editor\Manager::getToolbars();

            if (!empty($available)) {
                $User->setAttribute('toolbar', $available[0]);
            }
        }

        $User->addToGroup($Everyone->getUUID());
        $User->save($PermissionUser);

        QUI::getEvents()->fireEvent('userCreate', [$User]);

        // workspace
        $this->setDefaultWorkspacesForUsers($User);

        if (!empty($attributes)) {
            $User->setAttributes($attributes);
            $User->save($PermissionUser);
        }

        return $User;
    }

    /**
     * Returns the number of users in the system
     */
    public function countAllUsers(): int
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'count' => 'count',
                'from' => self::table()
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return 0;
        }

        if (isset($result[0]['count'])) {
            return $result[0]['count'];
        }

        return 0;
    }

    /**
     * @param boolean $objects - as objects=true, as array=false
     *
     * @return array
     */
    public function getAllUsers(bool $objects = false): array
    {
        if (!$objects) {
            try {
                return QUI::getDataBase()->fetch([
                    'from' => self::table(),
                    'order' => 'username'
                ]);
            } catch (QUi\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());

                return [];
            }
        }

        $result = [];
        $ids = $this->getAllUserIds();

        foreach ($ids as $id) {
            try {
                $result[] = $this->get($id['uuid']);
            } catch (QUI\Exception) {
                // nothing
            }
        }

        return $result;
    }

    public function getAllUserIds(): array
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id,uuid',
                'from' => self::table(),
                'order' => 'username'
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return [];
        }

        return $result;
    }

    /**
     * Logged in a user
     *
     * @param array|integer|string $authData - Authentication data, passwords, keys, hashes etc
     *
     * @return QUIUserInterface|null
     * @throws QUI\Users\Exception
     * @throws \Exception
     */
    public function login(array | int | string $authData = []): QUIUserInterface | null
    {
        if (QUI::getSession()->get('auth') && QUI::getSession()->get('uid')) {
            $userId = QUI::getSession()->get('uid');
            $this->Session = $this->get($userId);

            return $this->Session;
        }

        $Events = QUI::getEvents();
        $numArgs = func_num_args();
        $userId = false;

        // old login -> v 1.0; fallback
        if ($numArgs == 2) {
            $arguments = func_get_args();
            $authData = [
                'username' => $arguments[0],
                'password' => $arguments[1]
            ];
        }

        // try to get userId by authData
        if (!empty($authData['username'])) {
            try {
                $User = self::getUserByName($authData['username']);
                $userId = $User->getUUID();
            } catch (\Exception) {
                // nothing
            }
        }

        $Events->fireEvent('userLoginStart', [$userId]);

        // global authenticators
        if (QUI::getSession()->get('auth-globals') !== 1) {
            if (QUI::isBackend()) {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalBackendAuthenticators();
            } else {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalFrontendAuthenticators();
            }

            foreach ($authenticators as $authenticator) {
                $this->authenticate($authenticator, $authData);
            }

            if (!empty($authenticators)) {
                QUI::getSession()->set('auth-globals', 1);
                // @todo set user uuid to session
            }
        }

        if (QUI::getSession()->get('auth-secondary') !== 1) {
            if (QUI::isBackend()) {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalBackendSecondaryAuthenticators();
            } else {
                $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalFrontendSecondaryAuthenticators();
            }

            /* @var $Authenticator QUI\Users\AbstractAuthenticator */
            try {
                foreach ($authenticators as $authenticator) {
                    $this->authenticate($authenticator, $authData);
                }
            } catch (\Exception $exception) {
                throw new QUI\Users\Auth\Exception2FA(
                    $exception->getMessage()
                );
            }

            QUI::getSession()->set('auth-secondary', 1);
        }

        $userId = QUI::getSession()->get('uid');
        $User = $this->get($userId);

        if (QUI::getUsers()->isNobodyUser($User)) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_FOUND);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        // check user data
        $userData = QUI::getDataBase()->fetch(
            [
                'select' => ['id', 'uuid', 'expire', 'secHash', 'active'],
                'from' => self::table(),
                'where' => [
                    'uuid' => $userId
                ],
                'limit' => 1
            ]
        );

        if (!isset($userData[0])) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_FOUND);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        if ($userData[0]['active'] == 0) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user_not_active'],
                401
            );

            $Exception->setAttribute('userId', $userId);
            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_ACTIVE);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        if ($userData[0]['active'] == -1) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user_deleted'],
                401
            );

            $Exception->setAttribute('userId', $userId);
            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_DELETED);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        if (
            $userData[0]['expire']
            && $userData[0]['expire'] != '0000-00-00 00:00:00'
            && strtotime($userData[0]['expire']) < time()
        ) {
            $Exception = new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.login.expire', [
                    'expire' => $userData[0]['expire']
                ])
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_LOGIN_EXPIRED);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        /* @var $User User */
        // user authenticators
        $authenticator = $User->getAuthenticators();

        foreach ($authenticator as $Authenticator) {
            $this->authenticate($Authenticator, $authData);
        }

        // has user permission for a login
        QUI\Permissions\Permission::checkPermission('quiqqer.login', $User);


        // session
        QUI::getSession()->remove('inAuthentication');
        QUI::getSession()->set('auth', 1);
        QUI::getSession()->set('uid', $userId);
        QUI::getSession()->set('secHash', $this->getSecHash());

        $userAgent = '';

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        QUI::getDataBase()->update(
            self::table(),
            [
                'lastvisit' => time(),
                'user_agent' => $userAgent,
                'secHash' => $this->getSecHash()
            ],
            ['uuid' => $userId]
        );

        $User->refresh();
        $this->users[$userId] = $User;
        $this->Session = $User;

        QUI::getEvents()->fireEvent('userLogin', [$User]);

        return $User;
    }

    /**
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     */
    public function getUserByName(string $username): QUIUserInterface | User
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id,uuid',
                'from' => self::table(),
                'where' => [
                    'username' => $username
                ],
                'limit' => 1
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        return $this->get($result[0]['uuid']);
    }

    /**
     * Authenticate the user at one authenticator
     *
     * @throws QUI\Users\UserAuthException
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     */
    public function authenticate(
        AuthenticatorInterface | AbstractAuthenticator | string $authenticator,
        array $params = []
    ): bool {
        $username = '';
        $Session = QUI::getSession();

        // Wenn im Session ein Benutzernamen schon gesetzt wurde, von einem anderen Authenticator
        // Dann muss IMMER dieser Benutzer zur Authentifizierung verwendet werden
        if (QUI::getSession()->get('username')) {
            $username = QUI::getSession()->get('username');
        } elseif (isset($params['username'])) {
            $username = $params['username'];
        }

        // try to get user id
        $userId = false;

        if (!empty($username)) {
            try {
                $User = self::getUserByName($username);
                $userId = $User->getUUID();
            } catch (\Exception) {
                // nothing
            }
        }

        QUI::getEvents()->fireEvent('userAuthenticatorLoginStart', [$userId, $authenticator]);

        if ($authenticator instanceof AuthenticatorInterface) {
            $Authenticator = $authenticator;
        } else {
            $Authenticator = QUI\Users\Auth\Handler::getInstance()->getAuthenticator(
                $authenticator,
                $username
            );
        }

        if (
            $Session->get('auth-' . $Authenticator::class)
            && $Session->get('username')
            && $Session->get('uid')
        ) {
            return true;
        }

        try {
            $Authenticator->auth($params);
        } catch (QUI\Users\Exception $Exception) {
            $Exception->setAttribute('reason', self::AUTH_ERROR_AUTH_ERROR);

            QUI\System\Log::write(
                'Login failed: ' . $username,
                QUI\System\Log::LEVEL_WARNING,
                [],
                'auth'
            );

            QUI::getEvents()->fireEvent('userLoginError', [$userId, $Exception, $authenticator]);

            throw new QUI\Users\UserAuthException(
                $Exception->getMessage(),
                $Exception->getCode(),
                $Exception->getContext()
            );
        } catch (Throwable) {
            QUI\System\Log::write(
                'Login failed: ' . $username,
                QUI\System\Log::LEVEL_WARNING,
                [],
                'auth'
            );

            throw new QUI\Users\UserAuthException(
                ['quiqqer/core', 'exception.login.fail'],
                401
            );
        }

        // auth successful, set to session
        if (!$Session->get('username')) {
            $Session->set(
                'username',
                $Authenticator->getUser()->getUsername()
            );
        }

        if (!$Session->get('uid')) {
            $Session->set(
                'uid',
                $Authenticator->getUser()->getUUID()
            );
        }

        $Session->set(
            'auth-' . $Authenticator::class,
            1
        );

        return true;
    }

    public function isNobodyUser(mixed $User): bool
    {
        if (!is_object($User)) {
            return false;
        }

        if ($User::class === Nobody::class) {
            return true;
        }

        return false;
    }

    /**
     * Get specific users
     *
     * @param array $params -> SQL Array
     */
    public function getUsers(array $params = []): array
    {
        $result = $this->getUserIds($params);

        if (!isset($result[0])) {
            return [];
        }

        $Users = [];

        foreach ($result as $entry) {
            try {
                $Users[] = $this->get($entry['uuid']);
            } catch (QUI\Exception) {
                // nothing
            }
        }

        return $Users;
    }

    /**
     * Get specific users ids
     *
     * @param array $params -> SQL Array
     */
    public function getUserIds(array $params = []): array
    {
        $params['select'] = 'id,uuid';
        $params['from'] = self::table();

        try {
            return QUI::getDataBase()->fetch($params);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return [];
    }

    /**
     * Session initialize?
     */
    public function existsSession(): bool
    {
        return $this->Session !== null;
    }

    /**
     * this method is used for a cleanup of the ram.
     * individual user instances can be removed from the internal ram cache.
     */
    public function unsetUserInstance(QUIUserInterface $User): void
    {
        $uuid = $User->getUUID();
        $id = $User->getId();

        if (isset($this->users[$id])) {
            unset($this->users[$id]);
        }

        if (isset($this->usersUUIDs[$uuid])) {
            unset($this->usersUUIDs[$uuid]);
        }
    }

    /**
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     */
    public function getUserByMail(string $email): QUIUserInterface
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id,uuid',
                'from' => self::table(),
                'where' => [
                    'email' => $email
                ],
                'limit' => 1
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        return $this->get($result[0]['uuid']);
    }

    /**
     * @deprecated use usernameExists()
     */
    public function existsUsername(string $username): bool
    {
        return $this->usernameExists($username);
    }

    /**
     * @deprecated use existsUsername
     */
    public function checkUsername(string $username): bool
    {
        return $this->usernameExists($username);
    }

    /**
     * @deprecated use emailExists
     */
    public function existEmail(string $email): bool
    {
        return $this->emailExists($email);
    }

    /**
     * Checks the e-mail if this is already on the system
     */
    public function emailExists(string $email): bool
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'email',
                'from' => self::table(),
                'where' => [
                    'email' => $email
                ],
                'limit' => 1
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception);

            return false;
        }

        return isset($result[0]);
    }

    /**
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function deleteUser(int | string $id): bool
    {
        return $this->get($id)->delete();
    }

    public function onDeleteUser(QUIUserInterface $User): void
    {
        $id = $User->getId();
        $uuid = $User->getUUID();

        if (isset($this->users[$uuid])) {
            unset($this->users[$uuid]);
        }

        if (isset($this->usersUUIDs[$id])) {
            unset($this->usersUUIDs[$id]);
        }
    }

    /**
     * @throws Exception
     */
    public function search(array $params): array | int
    {
        return $this->execSearch($params);
    }

    /**
     * User search
     *
     * @throws QUI\Database\Exception
     * @todo where params
     */
    protected function execSearch(array $params): array | int
    {
        $PDO = QUI::getDataBase()->getPDO();
        $params = Orthos::clearArray($params);

        $allowOrderFields = [
            'id' => true,
            'uuid' => true,
            'email' => true,
            'username' => true,
            'usergroup' => true,
            'firstname' => true,
            'lastname' => true,
            'birthday' => true,
            'active' => true,
            'regdate' => true,
            'lastedit' => true,
            'su' => true
        ];

        $max = 10;
        $start = 0;

        /**
         * SELECT
         */
        $query = 'SELECT * FROM ' . self::table();
        $binds = [];

        if (isset($params['count'])) {
            $query = 'SELECT COUNT( id ) AS count FROM ' . self::table();
        }

        /**
         * WHERE
         */

        /**
         * WHERE Search
         */
        if (!empty($params['search'])) {
            if (empty($params['searchSettings'])) {
                $params['searchSettings'] = [];
            }

            if (!isset($params['searchSettings']['filter'])) {
                $params['searchSettings']['filter'] = [];
            }

            if (!isset($params['searchSettings']['fields'])) {
                $params['searchSettings']['fields'] = $allowOrderFields;
            }

            if (!isset($params['searchSettings']['userSearchString'])) {
                $params['searchSettings']['userSearchString'] = '';
            }

            $search = $params['searchSettings']['userSearchString'];
            $filter = $params['searchSettings']['filter'];
            $fields = $params['searchSettings']['fields'];

            // cleanup fields
            foreach ($fields as $field => $val) {
                $fields[$field] = (int)$val;

                if (!$fields[$field]) {
                    unset($fields[$field]);
                }
            }

            if (empty($fields)) {
                $fields = $allowOrderFields;
            }

            $filter_status = false;
            $filter_group = false;
            $filter_groups_exclude = false;
            $filter_regdate_first = false;
            $filter_regdate_last = false;

            // set the filters
            if (
                !empty($filter['filter_status'])
                && $filter['filter_status'] != 'all'
            ) {
                $filter_status = true;
            }

            if (!empty($filter['filter_group'])) {
                $filter_group = true;
            }

            if (!empty($filter['filter_groups_exclude'])) {
                $filter_groups_exclude = true;
            }

            if (!empty($filter['filter_regdate_first'])) {
                $filter_regdate_first = true;
            }

            if (!empty($filter['filter_regdate_last'])) {
                $filter_regdate_last = true;
            }


            // create the search
            if (empty($search)) {
                $query .= ' WHERE 1=1 ';
            } else {
                $query .= ' WHERE (';
                $binds[':search'] = '%' . $search . '%';

                foreach ($fields as $field => $value) {
                    if (!isset($allowOrderFields[$field])) {
                        continue;
                    }

                    if (empty($value)) {
                        continue;
                    }

                    $query .= ' ' . $field . ' LIKE :search OR ';
                }

                if (str_ends_with($query, 'OR ')) {
                    $query = substr($query, 0, -3);
                }

                $query .= ') ';
            }


            // empty where, no search possible
            if (str_contains($query, 'WHERE ()')) {
                return [];
            }


            if ($filter_status) {
                $query .= ' AND active = :active';
                $binds[':active'] = (int)$filter['filter_status'];
            }


            if ($filter_group) {
                $groups = explode(',', $filter['filter_group']);
                $subQuery = [];

                foreach ($groups as $g => $groupId) {
                    if ($groupId != 0) {
                        $subQuery[] = 'usergroup LIKE :g' . $g . ' ';

                        $binds[':g' . $g] = '%,' . $groupId . ',%';
                    }
                }

                $query .= ' AND (' . implode(' OR ', $subQuery) . ')';
            }

            if ($filter_groups_exclude) {
                $i = 0;

                foreach ($filter['filter_groups_exclude'] as $groupId) {
                    if ($groupId != 0) {
                        $query .= ' AND usergroup NOT LIKE :group' . $i . ' ';
                        $binds[':group' . $i] = '%,' . $groupId . ',%';
                    }

                    $i++;
                }
            }

            if ($filter_regdate_first) {
                $query .= ' AND regdate >= :firstreg ';
                $binds[':firstreg'] = QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_first'] . ' 00:00:00'
                );
            }


            if ($filter_regdate_last) {
                $query .= " AND regdate <= :lastreg ";
                $binds[':lastreg'] = QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_last'] . ' 00:00:00'
                );
            }
        }


        /**
         * ORDER
         */
        if (
            isset($params['order'])
            && isset($params['field'])
            && $params['field']
            && isset($allowOrderFields[$params['field']])
        ) {
            $query .= ' ORDER BY ' . $params['field'] . ' ' . $params['order'];
        }

        /**
         * LIMIT
         */
        if (isset($params['limit']) || isset($params['start'])) {
            if (isset($params['limit'])) {
                $max = (int)$params['limit'];
            }

            if (isset($params['start'])) {
                $start = (int)$params['start'];
            }

            $query .= ' LIMIT ' . $start . ', ' . $max;
        }

        $Statement = $PDO->prepare($query);


        foreach ($binds as $key => $value) {
            if ($key == ':active' || $key == ':su') {
                $Statement->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $Statement->bindValue($key, $value);
            }
        }

        try {
            $Statement->execute();
        } catch (PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= print_r($query, true);

            throw new QUI\Database\Exception(
                $message,
                $Exception->getCode()
            );
        }

        $result = $Statement->fetchAll(PDO::FETCH_ASSOC);


        if (isset($params['count'])) {
            return (int)$result[0]['count'];
        }

        return $result;
    }

    /**
     * User count
     *
     * @param array $params - Search parameter
     * @return integer
     *
     * @throws QUI\DataBase\Exception
     */
    public function count(array $params = []): int
    {
        $params['count'] = true;

        if (isset($params['limit'])) {
            unset($params['limit']);
        }

        if (isset($params['start'])) {
            unset($params['start']);
        }

        return (int)$this->execSearch($params);
    }

    /**
     * Create a new ID for a not created user
     *
     * @throws Exception
     * @deprecated
     */
    protected function newId(): int
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'MAX(id) AS id',
            'from' => self::table(),
            'limit' => 1
        ]);

        $newId = 100;

        if (isset($result[0]['id'])) {
            $newId = $result[0]['id'] + 1;
        }

        if ($newId < 100) {
            $newId = 100;
        }

        return $newId;
    }
}
