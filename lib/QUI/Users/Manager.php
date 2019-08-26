<?php

/**
 * This file contains \QUI\Users\Manager
 */

namespace QUI\Users;

use QUI;
use QUI\Utils\Security\Orthos;
use QUI\Utils\Text\XML;
use QUI\Utils\DOM;
use QUI\Security\Password;

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
    const AUTH_ERROR_LOGIN_EXPIRED = 'auth_error_login_expired';
    const AUTH_ERROR_NO_ACTIVE_GROUP = 'auth_error_no_active_group';

    /**
     * @var QUI\Projects\Project (active internal project)
     */
    private $Project = false;

    /**
     * @var array - list of users (cache)
     */
    private $users = [];

    /**
     * @var array
     */
    private $usersUUIDs = [];

    /**
     * @var null|Nobody
     */
    private $Nobody = null;

    /**
     * @var null|SystemUser
     */
    private $SystemUser = null;

    /**
     * @var null|User
     */
    private $Session = null;

    /**
     * internal prevention for multiple session user calling
     *
     * @var bool
     */
    protected $multipleCallPrevention = false;

    /**
     * Return the db table
     *
     * @return string
     */
    public static function table()
    {
        return QUI::getDBTableName('users');
    }

    /**
     * Return the db table for the addresses
     *
     * @return string
     */
    public static function tableAddress()
    {
        return QUI::getDBTableName('users_address');
    }

    /**
     * Create the database tables for the users
     *
     * @throws QUI\DataBase\Exception
     * @throws \Exception
     */
    public function setup()
    {
        $DataBase = QUI::getDataBase();
        $table    = self::table();

        // Patch strict
        $DataBase->getPDO()->exec(
            "ALTER TABLE `{$table}` 
            CHANGE `lastedit` `lastedit` DATETIME NULL DEFAULT NULL,
            CHANGE `expire` `expire` DATETIME NULL DEFAULT NULL,
            CHANGE `birthday` `birthday` DATE NULL DEFAULT NULL;
            "
        );

        try {
            $DataBase->getPDO()->exec("
                UPDATE `{$table}` 
                SET lastedit = NULL 
                WHERE 
                    lastedit = '0000-00-00 00:00:00' OR 
                    lastedit = '';
                    
                UPDATE `{$table}` 
                SET expire = NULL 
                WHERE 
                    expire = '0000-00-00 00:00:00' OR 
                    expire = '';
                    
                UPDATE `{$table}` 
                SET birthday = NULL 
                WHERE 
                    birthday = '0000-00-00' OR 
                    birthday = '';
            ");
        } catch (\PDOException $Exception) {
        }

        // uuid extrem indexes patch
        $Stmt = $DataBase->getPDO()->prepare(
            "SHOW INDEXES FROM `{$table}`
            WHERE 
                non_unique = 0 AND Key_name != 'PRIMARY';"
        );

        $Stmt->execute();
        $columns = $Stmt->fetchAll();
        $dropSql = [];

        foreach ($columns as $column) {
            if (\strpos($column['Key_name'], 'uuid_') === 0) {
                $dropSql[] = "ALTER TABLE `users` DROP INDEX `{$column['Key_name']}`;";
            }
        }

        if (!empty($dropSql)) {
            try {
                // foreach because of PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
                foreach ($dropSql as $sql) {
                    $Stmt = $DataBase->getPDO()->prepare($sql);
                    $Stmt->execute();
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeRecursive($dropSql);
                QUI\System\Log::writeException($Exception);
            }
        }

        // users with no uuid
        // @todo after 1.2 we can delete this
        $DataBase->table()->addColumn($table, [
            'uuid' => 'VARCHAR(50) NOT NULL'
        ]);

        $list = QUI::getDataBase()->fetch([
            'from'  => $table,
            'where' => [
                'uuid' => ''
            ]
        ]);

        foreach ($list as $entry) {
            $DataBase->update($table, [
                'uuid' => QUI\Utils\Uuid::get()
            ], [
                'id' => $entry['id']
            ]);
        }

        $DataBase->table()->setUniqueColumns($table, 'uuid');
    }

    /**
     * Is the user authenticated
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return boolean
     * @todo muss noch fremde nutzer prüfen
     *
     */
    public function isAuth($User)
    {
        if (!\is_object($User) || !$User->getId()) {
            return false;
        }

        try {
            $_User = $this->getUserBySession();
        } catch (QUI\Exception $Exception) {
            return false;
        }

        if ($User->getId() == $_User->getId()) {
            return true;
        }

        return false;
    }

    /**
     * Is the Object a User?
     * It checks the user interface, for authentication please use ->isAuth()
     *
     * @param mixed $User
     *
     * @return boolean
     */
    public function isUser($User)
    {
        if (!\is_object($User)) {
            return false;
        }

        if (\get_class($User) === User::class) {
            return true;
        }

        if ($User instanceof QUI\Interfaces\Users\User) {
            return true;
        }

        return false;
    }

    /**
     * Is the Object a systemuser?
     *
     * @param mixed $User
     *
     * @return boolean
     */
    public function isSystemUser($User)
    {
        if (!\is_object($User)) {
            return false;
        }

        if (\get_class($User) === SystemUser::class) {
            return true;
        }

        return false;
    }

    /**
     * Is the Object a systemuser?
     *
     * @param mixed $User
     *
     * @return boolean
     */
    public function isNobodyUser($User)
    {
        if (!\is_object($User)) {
            return false;
        }

        if (\get_class($User) === Nobody::class) {
            return true;
        }

        return false;
    }

    /**
     * Setzt das interne Projekt
     *
     * Für was???
     *
     * @param QUI\Projects\Project $Project
     *
     * @deprecated
     */
    public function setProject(QUI\Projects\Project $Project)
    {
        $this->Project = $Project;
    }

    /**
     * Gibt das interne Projekt zurück
     *
     *    Für was???
     *
     * @return     QUI\Projects\Project
     * @deprecated
     */
    public function getProject()
    {
        return $this->Project;
    }

    /**
     * Create a new User
     *
     * @param string|boolean $username - (optional), new username
     * @param QUI\Interfaces\Users\User $ParentUser - (optional), Parent User, which create the user
     *
     * @return QUI\Users\User
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function createChild($username = false, $ParentUser = null)
    {
        // check, is the user allowed to create new users
        QUI\Permissions\Permission::checkPermission(
            'quiqqer.admin.users.create',
            $ParentUser
        );

        if ($username) {
            if ($this->usernameExists($username)) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.lib.user.exist'
                    )
                );
            }

            $newName = $username;
        } else {
            $newUserLocale = QUI::getLocale()->get('quiqqer/quiqqer', 'user.create.new.username');
            $newName       = $newUserLocale;
            $i             = 0;

            while ($this->usernameExists($newName)) {
                $newName = $newUserLocale.' ('.$i.')';
                $i++;
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
            'uuid'     => $uuid,
            'username' => $newName,
            'regdate'  => \time(),
            'lang'     => QUI::getLocale()->getCurrent()
        ]);

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();
        $User  = $this->get($newId);

        // workspace
        $twoColumn   = QUI\Workspace\Manager::getTwoColumnDefault();
        $threeColumn = QUI\Workspace\Manager::getThreeColumnDefault();

        $newWorkspaceId = QUI\Workspace\Manager::addWorkspace(
            $User,
            QUI::getLocale()->get('quiqqer/quiqqer', 'workspaces.2.columns'),
            $twoColumn,
            500,
            700
        );

        QUI\Workspace\Manager::addWorkspace(
            $User,
            QUI::getLocale()->get('quiqqer/quiqqer', 'workspaces.3.columns'),
            $threeColumn,
            500,
            700
        );

        QUI\Workspace\Manager::setStandardWorkspace($User, $newWorkspaceId);

        $Everyone = new QUI\Groups\Everyone();

        $User->setAttribute('toolbar', $Everyone->getAttribute('toolbar'));

        if (!$User->getAttribute('toolbar')) {
            $available = QUI\Editor\Manager::getToolbars();

            if (!empty($available)) {
                $User->setAttribute('toolbar', $available[0]);
            }
        }

        $User->addToGroup($Everyone->getId());
        $User->save($ParentUser);

        QUI::getEvents()->fireEvent('userCreate', [$User]);

        return $User;
    }

    /**
     * Returns the number of users in the system
     *
     * @return integer
     */
    public function countAllUsers()
    {
        $result = QUI::getDataBase()->fetch([
            'count' => 'count',
            'from'  => self::table()
        ]);

        if (isset($result[0]) && isset($result[0]['count'])) {
            return $result[0]['count'];
        }

        return 0;
    }

    /**
     * Get all users
     *
     * @param boolean $objects - as objects=true, as array=false
     *
     * @return array
     */
    public function getAllUsers($objects = false)
    {
        if ($objects == false) {
            return QUI::getDataBase()->fetch([
                'from'  => self::table(),
                'order' => 'username'
            ]);
        }

        $result = [];
        $ids    = $this->getAllUserIds();

        foreach ($ids as $id) {
            try {
                $result[] = $this->get((int)$id['id']);
            } catch (QUI\Exception $Exception) {
                // nothing
            }
        }

        return $result;
    }

    /**
     * Returns all user-IDs
     *
     * @return array
     */
    public function getAllUserIds()
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => self::table(),
            'order'  => 'username'
        ]);

        return $result;
    }

    /**
     * Get specific users
     *
     * @param array $params -> SQL Array
     *
     * @return array
     */
    public function getUsers($params = [])
    {
        $result = $this->getUserIds($params);

        if (!isset($result[0])) {
            return [];
        }

        $Users = [];

        foreach ($result as $entry) {
            try {
                $Users[] = $this->get((int)$entry['id']);
            } catch (QUI\Exception $Exception) {
                // nothing
            }
        }

        return $Users;
    }

    /**
     * Get specific users ids
     *
     * @param array $params -> SQL Array
     *
     * @return array
     */
    public function getUserIds($params = [])
    {
        $params['select'] = 'id';
        $params['from']   = self::table();

        return QUI::getDataBase()->fetch($params);
    }

    /**
     * Authenticate the user at one authenticator
     *
     * @param string|AbstractAuthenticator|AuthenticatorInterface $authenticator
     * @param array $params
     * @return bool
     *
     * @throws QUI\Users\UserAuthException
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     */
    public function authenticate($authenticator, $params = [])
    {
        $username = '';
        $Session  = QUI::getSession();

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
                $User   = self::getUserByName($username);
                $userId = $User->getId();
            } catch (\Exception $Exception) {
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

        if ($Session->get('auth-'.\get_class($Authenticator))
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
                'Login failed: '.$username,
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
        } catch (\Exception $Exception) {
            QUI\System\Log::write(
                'Login failed: '.$username,
                QUI\System\Log::LEVEL_WARNING,
                [],
                'auth'
            );

            throw new QUI\Users\UserAuthException(
                ['quiqqer/system', 'exception.login.fail'],
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
                $Authenticator->getUser()->getId()
            );
        }

        $Session->set(
            'auth-'.\get_class($Authenticator),
            1
        );

        return true;
    }

    /**
     * Logged in a user
     *
     * @param string|array|integer $authData - Authentication data, passwords, keys, hashes etc
     *
     * @return QUI\Interfaces\Users\User
     * @throws QUI\Users\Exception
     * @throws \Exception
     */
    public function login($authData = [])
    {
        if (QUI::getSession()->get('auth') && QUI::getSession()->get('uid')) {
            $userId        = QUI::getSession()->get('uid');
            $this->Session = $this->get($userId);

            return $this->Session;
        }

        $Events  = QUI::getEvents();
        $numArgs = \func_num_args();
        $userId  = false;

        // old login -> v 1.0; fallback
        if ($numArgs == 2) {
            $arguments = \func_get_args();
            $authData  = [
                'username' => $arguments[0],
                'password' => $arguments[1]
            ];
        }

        // try to get userId by authData
        if (!empty($authData['username'])) {
            try {
                $User   = self::getUserByName($authData['username']);
                $userId = $User->getId();
            } catch (\Exception $Exception) {
                // nothing
            }
        }

        $Events->fireEvent('userLoginStart', [$userId]);

        // global authenticators
        if (QUI::getSession()->get('auth-globals') !== 1) {
            $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalAuthenticators();

            /* @var $Authenticator QUI\Users\AbstractAuthenticator */
            foreach ($authenticators as $authenticator) {
                $this->authenticate($authenticator, $authData);
            }

            QUI::getSession()->set('auth-globals', 1);
        }

        $userId = QUI::getSession()->get('uid');
        $User   = $this->get($userId);

        if (QUI::getUsers()->isNobodyUser($User)) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail.user.not.found'],
                404
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_FOUND);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        // check user data
        $userData = QUI::getDataBase()->fetch(
            [
                'select' => ['id', 'expire', 'secHash', 'active'],
                'from'   => self::table(),
                'where'  => [
                    'id' => $userId
                ],
                'limit'  => 1
            ]
        );

        if (!isset($userData[0])) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail.user.not.found'],
                404
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_FOUND);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        if ($userData[0]['active'] != 1) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail.user_not_active'],
                401
            );

            $Exception->setAttribute('userId', $userId);
            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_ACTIVE);

            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

        if ($userData[0]['expire']
            && $userData[0]['expire'] != '0000-00-00 00:00:00'
            && \strtotime($userData[0]['expire']) < \time()
        ) {
            $Exception = new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.login.expire', [
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

        // is one group active?
        $activeGroupExists = false;

        foreach ($User->getGroups() as $Group) {
            /* @var $Group QUI\Groups\Group */
            if ($Group->getAttribute('active') == 1) {
                $activeGroupExists = true;
                break;
            }
        }

        if ($activeGroupExists === false) {
            $Exception = new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail'],
                401
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_NO_ACTIVE_GROUP);
            $Events->fireEvent('userLoginError', [$userId, $Exception]);

            throw $Exception;
        }

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
                'lastvisit'  => \time(),
                'user_agent' => $userAgent,
                'secHash'    => $this->getSecHash()
            ],
            ['id' => $userId]
        );

        $User->refresh();
        $this->users[$userId] = $User;
        $this->Session        = $User;

        QUI::getEvents()->fireEvent('userLogin', [$User]);

        return $User;
    }

    /**
     * Generate a user-dependent security hash
     * There are different data use such as IP, User-Agent and the System-Salt
     *
     * @return string
     * @todo   noch eine eindeutige möglichkeit der Identifizierung des Browser finden
     */
    public function getSecHash()
    {
        $secHashData = [];
        $useragent   = '';

        // chromeframe nicht mitaufnehmen -> bug
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && \strpos($_SERVER['HTTP_USER_AGENT'], 'chromeframe') === false
        ) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }

        $secHashData[] = $useragent;
        $secHashData[] = QUI\Utils\System::getClientIP();
        $secHashData[] = QUI::conf('globals', 'salt');

        return \md5(\serialize($secHashData));
    }

    /**
     * Get the Session user
     *
     * @return QUI\Interfaces\Users\User
     */
    public function getUserBySession()
    {
        if (\defined('SYSTEM_INTERN')) {
            return $this->getSystemUser();
        }

        if ($this->Session !== null) {
            return $this->Session;
        }

        if ($this->multipleCallPrevention) {
            return $this->getNobody();
        }


        $this->multipleCallPrevention = true;

        // max_life_time check
        try {
            $this->checkUserSession();
            $this->Session = $this->get(QUI::getSession()->get('uid'));
        } catch (QUI\Exception $Exception) {
            if (DEBUG_MODE) {
                QUI\System\Log::writeDebugException($Exception);
            }

            $this->Session = $this->getNobody();
        }

        return $this->Session;
    }

    /**
     * Session initialize?
     *
     * @return boolean
     */
    public function existsSession()
    {
        return $this->Session !== null;
    }

    /**
     * Checks, if the session is ok
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function checkUserSession()
    {
        // max_life_time check
        $Session = QUI::getSession();

        $clearSessionData = function () use ($Session) {
            $sessionData = $Session->getSymfonySession()->all();

            foreach ($sessionData as $key => $value) {
                if (\strpos($key, 'auth-') === 0) {
                    $Session->remove($key);
                }
            }
        };

        if (!$Session->check()) {
            $clearSessionData();

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
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
                        'quiqqer/system',
                        'exception.session.expired.from.other'
                    ),
                    401
                );
            }

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
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
                    'quiqqer/system',
                    'exception.user.inactive'
                ),
                401
            );
        }

        // Mehrfachanmeldungen? Dann keine Prüfung
        if (QUI::conf('session', 'multible')
            || $Session->get('inAuthentication')
        ) {
            return;
        }

        $sessionSecHash = $Session->get('secHash');
        $secHash        = $this->getSecHash();
        $userSecHash    = $User->getAttribute('secHash');

        if ($sessionSecHash == $secHash && $userSecHash == $secHash) {
            return;
        }

        $message = $User->getLocale()->get(
            'quiqqer/system',
            'exception.session.expired.from.other'
        );

        $Session->set('uid', 0);
        $Session->getSymfonySession()->clear();
        $Session->refresh();
        $Session->set('expired.from.other', 1);

        throw new QUI\Users\Exception($message, 401);
    }

    /**
     * Return the Nobody user
     *
     * @return QUI\Users\Nobody
     */
    public function getNobody()
    {
        if ($this->Nobody === null) {
            $this->Nobody = new Nobody();
        }

        return $this->Nobody;
    }

    /**
     * Return the System user
     *
     * @return QUI\Users\SystemUser
     */
    public function getSystemUser()
    {
        if ($this->SystemUser === null) {
            $this->SystemUser = new SystemUser();
        }

        return $this->SystemUser;
    }

    /**
     * Get the user by id
     *
     * @param integer $id
     * @return QUI\Users\User|Nobody|SystemUser|false
     *
     * @throws QUI\Users\Exception
     */
    public function get($id)
    {
        if (\is_numeric($id)) {
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

        $User = new User($id, $this);
        $uuid = $User->getUniqueId();

        $this->usersUUIDs[$uuid] = $User->getId();
        $this->users[$id]        = $User;

        return $User;
    }

    /**
     * get the user by username
     *
     * @param string $username - Username
     *
     * @return QUI\Users\User
     * @throws QUI\Users\Exception
     */
    public function getUserByName($username)
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => self::table(),
            'where'  => [
                'username' => $username
            ],
            'limit'  => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        return $this->get((int)$result[0]['id']);
    }

    /**
     * Get the user by email
     *
     * @param string $email - User E-Mail
     *
     * @return QUI\Users\User
     * @throws QUI\Users\Exception
     */
    public function getUserByMail($email)
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => self::table(),
            'where'  => [
                'email' => $email
            ],
            'limit'  => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        return $this->get($result[0]['id']);
    }

    /**
     * @param string $username
     *
     * @return string
     * @deprecated use usernameExists()
     */
    public function existsUsername($username)
    {
        return $this->usernameExists($username);
    }

    /**
     * Checks if the username already exists
     *
     * @param string $username
     *
     * @return boolean
     */
    public function usernameExists($username)
    {
        if (empty($username)) {
            return false;
        }

        $result = QUI::getDataBase()->fetch([
            'select' => 'username',
            'from'   => self::table(),
            'where'  => [
                'username' => $username
            ],
            'limit'  => 1
        ]);

        return isset($result[0]) ? true : false;
    }

    /**
     * @param string $username
     *
     * @return string
     * @deprecated use existsUsername
     */
    public function checkUsername($username)
    {
        return $this->usernameExists($username);
    }

    /**
     * @param string $email
     *
     * @return string
     * @deprecated use emailExists
     */
    public function existEmail($email)
    {
        return $this->emailExists($email);
    }

    /**
     * Checks the e-mail if this is already on the system
     *
     * @param string $email
     *
     * @return boolean
     */
    public function emailExists($email)
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'email',
            'from'   => self::table(),
            'where'  => [
                'email' => $email
            ],
            'limit'  => 1
        ]);

        return isset($result[0]) ? true : false;
    }

    /**
     * @param string $pass
     * @param string $salt -> deprecated
     *
     * @return string
     * @deprecated
     *
     * Generates a hash of a password
     *
     */
    public static function genHash($pass, $salt = null)
    {
        return Password::generateHash($pass);
    }

    /**
     * Delete the user
     *
     * @param integer $id
     *
     * @return boolean
     *
     * @throws QUI\Users\Exception
     */
    public function deleteUser($id)
    {
        return $this->get($id)->delete();
    }

    /**
     * Search all users
     *
     * @param array $params
     *
     * @return array
     *
     * @throws QUI\DataBase\Exception
     */
    public function search($params)
    {
        return $this->execSearch($params);
    }

    /**
     * Anzahl der Benutzer
     *
     * @param array $params - Search parameter
     *
     * @return integer
     *
     * @throws QUI\DataBase\Exception
     */
    public function count($params = [])
    {
        $params['count'] = true;

        if (isset($params['limit'])) {
            unset($params['limit']);
        }

        if (isset($params['start'])) {
            unset($params['start']);
        }

        return $this->execSearch($params);
    }

    /**
     * Suche ausführen
     *
     * @param array $params
     * @return array|integer
     *
     * @throws QUI\Database\Exception
     * @todo where params
     *
     */
    protected function execSearch($params)
    {
        $PDO    = QUI::getDataBase()->getPDO();
        $params = Orthos::clearArray($params);

        $allowOrderFields = [
            'id'        => true,
            'email'     => true,
            'username'  => true,
            'usergroup' => true,
            'firstname' => true,
            'lastname'  => true,
            'birthday'  => true,
            'active'    => true,
            'regdate'   => true,
            'su'        => true
        ];

        $max   = 10;
        $start = 0;

        /**
         * SELECT
         */
        $query = 'SELECT * FROM '.self::table();
        $binds = [];

        if (isset($params['count'])) {
            $query = 'SELECT COUNT( id ) AS count FROM '.self::table();
        }

        /**
         * WHERE
         */
//        if (isset($params['where'])) {
        // $_fields['where'] = $params['where'];
//        }

        // wenn nicht durchsucht wird dann gelöschte nutzer nicht anzeigen
//        if (!isset($params['search'])) {
        // $_fields['where_relation']  = "`active` != '-1' ";
//        }


        /**
         * WHERE Search
         */
        if (isset($params['search']) && $params['search'] == true) {
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

            $filter_status         = false;
            $filter_group          = false;
            $filter_groups_exclude = false;
            $filter_regdate_first  = false;
            $filter_regdate_last   = false;

            // set the filters
            if (isset($filter['filter_status'])
                && !empty($filter['filter_status'])
                && $filter['filter_status'] != 'all'
            ) {
                $filter_status = true;
            }

            if (isset($filter['filter_group']) && !empty($filter['filter_group'])) {
                $filter_group = true;
            }

            if (isset($filter['filter_groups_exclude']) && !empty($filter['filter_groups_exclude'])) {
                $filter_groups_exclude = true;
            }

            if (isset($filter['filter_regdate_first']) && !empty($filter['filter_regdate_first'])) {
                $filter_regdate_first = true;
            }

            if (isset($filter['filter_regdate_last']) && !empty($filter['filter_regdate_last'])) {
                $filter_regdate_last = true;
            }


            // create the search
            if (empty($search)) {
                $query .= ' WHERE 1=1 ';
            } else {
                $query            .= ' WHERE (';
                $binds[':search'] = '%'.$search.'%';

                if (empty($search)) {
                    $binds[':search'] = '%';
                }

                foreach ($fields as $field => $value) {
                    if (!isset($allowOrderFields[$field])) {
                        continue;
                    }

                    if (empty($value)) {
                        continue;
                    }

                    $query .= ' '.$field.' LIKE :search OR ';
                }

                if (\substr($query, -3) == 'OR ') {
                    $query = \substr($query, 0, -3);
                }

                $query .= ') ';
            }


            // empty where, no search possible
            if (\strpos($query, 'WHERE ()') !== false) {
                return [];
            }


            if ($filter_status) {
                $query            .= ' AND active = :active';
                $binds[':active'] = (int)$filter['filter_status'];
            }


            if ($filter_group) {
                $groups = \explode(',', $filter['filter_group']);

                foreach ($groups as $groupId) {
                    if ((int)$groupId > 0) {
                        $query               .= ' AND usergroup LIKE :'.$groupId.' ';
                        $binds[':'.$groupId] = '%,'.(int)$groupId.',%';
                    }
                }
            }

            if ($filter_groups_exclude) {
                foreach ($filter['filter_groups_exclude'] as $groupId) {
                    if ((int)$groupId > 0) {
                        $query               .= ' AND usergroup NOT LIKE :'.$groupId.' ';
                        $binds[':'.$groupId] = '%,'.(int)$groupId.',%';
                    }
                }
            }

            if ($filter_regdate_first) {
                $query              .= ' AND regdate >= :firstreg ';
                $binds[':firstreg'] = QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_first'].' 00:00:00'
                );
            }


            if ($filter_regdate_last) {
                $query             .= " AND regdate <= :lastreg ";
                $binds[':lastreg'] = QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_last'].' 00:00:00'
                );
            }
        }


        /**
         * ORDER
         */
        if (isset($params['order'])
            && isset($params['field'])
            && $params['field']
            && isset($allowOrderFields[$params['field']])
        ) {
            $query .= ' ORDER BY '.$params['field'].' '.$params['order'];
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

            $query .= ' LIMIT '.$start.', '.$max;
        }

        $Statement = $PDO->prepare($query);


        foreach ($binds as $key => $value) {
            if ($key == ':active' || $key == ':su') {
                $Statement->bindValue($key, $value, \PDO::PARAM_INT);
            } else {
                $Statement->bindValue($key, $value, \PDO::PARAM_STR);
            }
        }

        try {
            $Statement->execute();
        } catch (\PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= \print_r($query, true);

            throw new QUI\Database\Exception(
                $message,
                $Exception->getCode()
            );
        }

        $result = $Statement->fetchAll(\PDO::FETCH_ASSOC);


        if (isset($params['count'])) {
            return (int)$result[0]['count'];
        }

        return $result;
    }

    /**
     * Create a new ID for a not created user
     *
     * @return integer
     * @deprecated
     */
    protected function newId()
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'MAX(id) AS id',
            'from'   => self::table(),
            'limit'  => 1
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

    /**
     * Delete illegal characters from the name
     *
     * @param string $username
     *
     * @return boolean
     */
    public static function clearUsername($username)
    {
        return \preg_replace('/[^a-zA-Z0-9-_äöüß@\.\+]/', '', $username);
    }

    /**
     * Checks name for illegal characters
     *
     * @param string $username
     *
     * @return boolean
     * @throws QUI\Users\Exception
     */
    public static function checkUsernameSigns($username)
    {
        if ($username != self::clearUsername($username)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.lib.user.illegal.signs')
            );
        }

        return true;
    }

    /**
     * Get user profile template (profile window)
     *
     * @return string
     */
    public static function getProfileTemplate()
    {
        try {
            $Engine = QUI::getTemplateManager()->getEngine(true);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return '';
        }

        $packages = QUI::getPackageManager()->getInstalled();
        $extend   = '';

        foreach ($packages as $package) {
            $name    = $package['name'];
            $userXml = OPT_DIR.$name.'/user.xml';

            if (!\file_exists($userXml)) {
                continue;
            }

            $Document = XML::getDomFromXml($userXml);
            $Path     = new \DOMXPath($Document);

            $tabs = $Path->query("//user/profile/tab");

            /* @var $Tab \DOMElement */
            foreach ($tabs as $Tab) {
                try {
                    $extend .= DOM::parseCategoryToHTML($Tab);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }
        }

        $Engine->assign([
            'QUI'    => new QUI(),
            'extend' => $extend
        ]);

        return $Engine->fetch(SYS_DIR.'template/users/profile.html');
    }
}
