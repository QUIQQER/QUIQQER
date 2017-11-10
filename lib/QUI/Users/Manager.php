<?php

/**
 * This file contains \QUI\Users\Manager
 */

namespace QUI\Users;

use function GuzzleHttp\Promise\queue;
use QUI;
use QUI\Utils\Security\Orthos;
use QUI\Utils\Text\XML;
use QUI\Utils\DOM;
use QUI\Security\Password;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

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
    const AUTH_ERROR_PRIMARY_AUTH_ERROR   = 'auth_error_primary_auth_error';
    const AUTH_ERROR_SECONDARY_AUTH_ERROR = 'auth_error_secondary_auth_error';
    const AUTH_ERROR_USER_NOT_FOUND       = 'auth_error_user_not_found';
    const AUTH_ERROR_USER_NOT_ACTIVE      = 'auth_error_user_not_active';
    const AUTH_ERROR_LOGIN_EXPIRED        = 'auth_error_login_expired';
    const AUTH_ERROR_NO_ACTIVE_GROUP      = 'auth_error_no_active_group';

    /**
     * @var QUI\Projects\Project (active internal project)
     */
    private $Project = false;

    /**
     * @var array - list of users (cache)
     */
    private $users = array();

    /**
     * @var array
     */
    private $usersUUIDs = array();

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
        return QUI_DB_PRFX . 'users';
    }

    /**
     * Return the db table for the addresses
     *
     * @return string
     */
    public static function tableAddress()
    {
        return QUI_DB_PRFX . 'users_address';
    }

    /**
     * Create the database tables for the users
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

        // Addresses
        $DataBase->table()->addColumn(self::tableAddress(), array(
            'id'         => 'INT(11)',
            'uid'        => 'INT(11)',
            'salutation' => 'VARCHAR(10)',
            'firstname'  => 'VARCHAR(40)',
            'lastname'   => 'VARCHAR(40)',
            'phone'      => 'TEXT NULL',
            'mail'       => 'TEXT NULL',
            'company'    => 'VARCHAR(100)',
            'delivery'   => 'TEXT NULL',
            'street_no'  => 'TEXT NULL',
            'zip'        => 'TEXT NULL',
            'city'       => 'TEXT NULL',
            'country'    => 'TEXT NULL'
        ));

        $DataBase->table()->setIndex(self::tableAddress(), 'id');

        $tableAddress = self::tableAddress();

        $DataBase->getPDO()->exec(
            "ALTER TABLE `{$tableAddress}` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT"
        );

        // users with no uuid
        $DataBase->table()->addColumn($table, array(
            'uuid' => 'VARCHAR(50) NOT NULL'
        ));

        $list = QUI::getDataBase()->fetch(array(
            'from'  => $table,
            'where' => array(
                'uuid' => ''
            )
        ));

        foreach ($list as $entry) {
            try {
                $uuid = Uuid::uuid1()->toString();
            } catch (UnsatisfiedDependencyException $Exception) {
                QUI\System\Log::writeException($Exception);
                continue;
            }

            QUI::getDataBase()->update($table, array(
                'uuid' => $uuid
            ), array(
                'id' => $entry['id']
            ));
        }
    }

    /**
     * Is the user authenticated
     *
     * @todo muss noch fremde nutzer prüfen
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return boolean
     */
    public function isAuth($User)
    {
        if (!is_object($User) || !$User->getId()) {
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
        if (!is_object($User)) {
            return false;
        }

        if (get_class($User) === User::class) {
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
        if (!is_object($User)) {
            return false;
        }

        if (get_class($User) === SystemUser::class) {
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
        if (!is_object($User)) {
            return false;
        }

        if (get_class($User) === Nobody::class) {
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
     */
    public function createChild($username = false, $ParentUser = null)
    {
        // check, is the user allowed to create new users
        QUI\Permissions\Permission::checkPermission(
            'quiqqer.admin.users.create',
            $ParentUser
        );

        $newId = $this->newId();

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
                $newName = $newUserLocale . ' (' . $i . ')';
                $i++;
            }
        }

        self::checkUsernameSigns($username);

        QUI::getDataBase()->insert(self::table(), array(
            'id'       => $newId,
            'username' => $newName,
            'regdate'  => time(),
            'lang'     => QUI::getLocale()->getCurrent()
        ));

        $User = $this->get($newId);

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

        return $User;
    }

    /**
     * Register a user
     *
     * @param array $params
     *
     * @return User
     * @throws QUI\Users\Exception
     *
     * @needle
     * <ul>
     *   <li>$param['username']</li>
     *   <li>$param['password']</li>
     * </ul>
     *
     * @optional
     * <ul>
     *   <li>$param['firstname']</li>
     *     <li>$param['lastname']</li>
     *     <li>$param['usertitle']</li>
     *     <li>$param['birthday']</li>
     *     <li>$param['email']</li>
     *     <li>$param['lang']</li>
     *     <li>$param['expire']</li>
     *     <li>$param['usergroup']</li>
     * </ul>
     *
     * @todo use bind params
     */
    public function register($params)
    {
        if (!isset($params['username'])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.register.specify.username'
                )
            );
        }

        if (!isset($params['password'])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    ''
                )
            );
        }

        $username = $params['username'];
        $password = QUI\Security\Password::generateHash($params['password']);

        // unerlaubte zeichen prüfen
        self::checkUsernameSigns($username);

        if ($this->usernameExists($username)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.register.specify.password'
                )
            );
        }

        $regparams = array();
        $optional  = array(
            'firstname',
            'lastname',
            'usertitle',
            'birthday',
            'email',
            'lang',
            'expire',
            'usergroup'
        );

        $rootid = QUI::conf('globals', 'root');

        foreach ($optional as $key) {
            if (!isset($params[$key])) {
                continue;
            }

            $value = $params[$key];

            // Benutzergruppen gesondert behandeln - darf nicht in die Root Gruppe
            if ($key == 'usergroup') {
                $_gids = explode(',', $value);
                $gids  = array();

                foreach ($_gids as $gid) {
                    if (!empty($gid) && $gid != $rootid) {
                        $gids[] = (int)$gid;
                    }
                }

                $regparams['usergroup'] = ',' . implode(',', $gids) . ',';
                continue;
            }

            // $regparams[ $key ] = Orthos::clearMySQL( $params[ $key ] );
            $regparams[$key] = $params[$key];
        }

        $useragent = '';

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }

        $Session = QUI::getSession();

        $regparams['id']         = $this->newId();
        $regparams['su']         = 0;
        $regparams['username']   = $username;
        $regparams['password']   = $password;
        $regparams['active']     = 0;
        $regparams['activation'] = Orthos::getPassword(20);
        $regparams['regdate']    = time();
        $regparams['lastedit']   = date('Y-m-d H:i:s');
        $regparams['user_agent'] = $useragent;

        if ($Session->get('ref')) {
            $regparams['referal'] = $Session->get('ref');
        }

        QUI::getDataBase()->insert(self::table(), $regparams);

        $lastId = QUI::getDataBase()->getPDO()->lastInsertId('id');

        return $this->get((int)$lastId);
    }

    /**
     * Returns the number of users in the system
     *
     * @return integer
     */
    public function countAllUsers()
    {
        $result = QUI::getDataBase()->fetch(
            array(
                'count' => 'count',
                'from'  => self::table()
            )
        );

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
            return QUI::getDataBase()->fetch(
                array(
                    'from'  => self::table(),
                    'order' => 'username'
                )
            );
        }

        $result = array();
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
        $result = QUI::getDataBase()->fetch(
            array(
                'select' => 'id',
                'from'   => self::table(),
                'order'  => 'username'
            )
        );

        return $result;
    }

    /**
     * Get specific users
     *
     * @param array $params -> SQL Array
     *
     * @return array
     */
    public function getUsers($params = array())
    {
        $result = $this->getUserIds($params);

        if (!isset($result[0])) {
            return array();
        }

        $Users = array();

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
    public function getUserIds($params = array())
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
     * @throws QUI\Users\Exception
     */
    public function authenticate($authenticator, $params = array())
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

        if ($authenticator instanceof AuthenticatorInterface) {
            $Authenticator = $authenticator;
        } else {
            $Authenticator = QUI\Users\Auth\Handler::getInstance()->getAuthenticator(
                $authenticator,
                $username
            );
        }

        if ($Session->get('auth-' . get_class($Authenticator))
            && $Session->get('username')
            && $Session->get('uid')
        ) {
            return true;
        }

        try {
            $Authenticator->auth($params);
        } catch (QUI\Users\Exception $Exception) {
            throw $Exception;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail'),
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
            'auth-' . get_class($Authenticator),
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
    public function login($authData = array())
    {
        if (QUI::getSession()->get('auth')
            && QUI::getSession()->get('uid')
        ) {
            $userId        = QUI::getSession()->get('uid');
            $this->Session = $this->get($userId);

            return $this->Session;
        }

        $Events  = QUI::getEvents();
        $numArgs = func_num_args();
        $userId  = false;

        // old login -> v 1.0; fallback
        if ($numArgs == 2) {
            $arguments = func_get_args();
            $authData  = array(
                'username' => $arguments[0],
                'password' => $arguments[1]
            );
        }

        // global authenticators
        if (QUI::getSession()->get('auth-globals') !== 1) {
            $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalAuthenticators();

            /* @var $Authenticator QUI\Users\AbstractAuthenticator */
            foreach ($authenticators as $authenticator) {
                try {
                    $this->authenticate($authenticator, $authData);
                } catch (\Exception $Exception) {
                    $Exception = new QUI\Users\Exception(
                        array('quiqqer/system', 'exception.login.fail.authenticator_error'),
                        404
                    );

                    $Exception->setAttribute('reason', self::AUTH_ERROR_PRIMARY_AUTH_ERROR);

                    $Events->fireEvent('userLoginError', array($userId, $Exception));

                    throw $Exception;
                }
            }

            QUI::getSession()->set('auth-globals', 1);
        }

        $userId = QUI::getSession()->get('uid');

        $Events->fireEvent('userLoginStart', array($userId));

        $User = $this->get($userId);

        if (QUI::getUsers()->isNobodyUser($User)) {
            $Exception = new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.user.not.found'),
                404
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_FOUND);

            $Events->fireEvent('userLoginError', array($userId, $Exception));

            throw $Exception;
        }

        // check user data
        $userData = QUI::getDataBase()->fetch(
            array(
                'select' => array('id', 'expire', 'secHash', 'active'),
                'from'   => self::table(),
                'where'  => array(
                    'id' => $userId
                ),
                'limit'  => 1
            )
        );

        if (!isset($userData[0])) {
            $Exception = new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.user.not.found'),
                404
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_FOUND);

            $Events->fireEvent('userLoginError', array($userId, $Exception));

            throw $Exception;
        }

        if ($userData[0]['active'] == 0) {
            $Exception = new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.user_not_active'),
                401
            );

            $Exception->setAttribute('userId', $userId);
            $Exception->setAttribute('reason', self::AUTH_ERROR_USER_NOT_ACTIVE);

            $Events->fireEvent('userLoginError', array($userId, $Exception));

            throw $Exception;
        }

        if ($userData[0]['expire']
            && $userData[0]['expire'] != '0000-00-00 00:00:00'
            && strtotime($userData[0]['expire']) < time()
        ) {
            $Exception = new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.login.expire', array(
                    'expire' => $userData[0]['expire']
                ))
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_LOGIN_EXPIRED);

            $Events->fireEvent('userLoginError', array($userId, $Exception));

            throw $Exception;
        }

        /* @var $User User */
        // user authenticators
        $authenticator = $User->getAuthenticators();

        try {
            foreach ($authenticator as $Authenticator) {
                $this->authenticate($Authenticator, $authData);
            }
        } catch (\Exception $Exception) {
            $Events->fireEvent('userLoginError', array($userId, $Exception));

            if (method_exists($Exception, 'setAttribute')) {
                $Exception->setAttribute('reason', self::AUTH_ERROR_SECONDARY_AUTH_ERROR);
            }

            throw $Exception;
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
                array('quiqqer/system', 'exception.login.fail'),
                401
            );

            $Exception->setAttribute('reason', self::AUTH_ERROR_NO_ACTIVE_GROUP);
            $Events->fireEvent('userLoginError', array($userId, $Exception));

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
            array(
                'lastvisit'  => time(),
                'user_agent' => $userAgent,
                'secHash'    => $this->getSecHash()
            ),
            array('id' => $userId)
        );

        $User->refresh();
        $this->users[$userId] = $User;
        $this->Session        = $User;

        QUI::getEvents()->fireEvent('userLogin', array($User));

        return $User;
    }

    /**
     * Generate a user-dependent security hash
     * There are different data use such as IP, User-Agent and the System-Salt
     *
     * @todo   noch eine eindeutige möglichkeit der Identifizierung des Browser finden
     * @return string
     */
    public function getSecHash()
    {
        $secHashData = array();
        $useragent   = '';

        // chromeframe nicht mitaufnehmen -> bug
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && strpos($_SERVER['HTTP_USER_AGENT'], 'chromeframe') === false
        ) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }

        $secHashData[] = $useragent;
        $secHashData[] = QUI\Utils\System::getClientIP();
        $secHashData[] = QUI::conf('globals', 'salt');

        return md5(serialize($secHashData));
    }

    /**
     * Get the Session user
     *
     * @return QUI\Interfaces\Users\User
     */
    public function getUserBySession()
    {
        if (defined('SYSTEM_INTERN')) {
            return $this->getSystemUser();
        }

        if (!is_null($this->Session)) {
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
        return !is_null($this->Session);
    }

    /**
     * Checks, if the session is ok
     *
     * @throws QUI\Users\Exception
     */
    public function checkUserSession()
    {
        // max_life_time check
        $Session = QUI::getSession();

        $clearSessionData = function () use ($Session) {
            $sessionData = $Session->getSymfonySession()->all();

            foreach ($sessionData as $key => $value) {
                if (strpos($key, 'auth-') === 0) {
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
     * @param  integer $id
     * @return QUI\Users\User|Nobody|SystemUser|false
     *
     * @throws QUI\Users\Exception
     */
    public function get($id)
    {
        $id = (int)$id;

        if (!$id) {
            return new Nobody();
        }

        if ($id == 5) {
            return new SystemUser();
        }

        if (isset($this->users[$id])) {
            return $this->users[$id];
        }

        $User             = new User($id, $this);
        $this->users[$id] = $User;

        return $User;
    }

    /**
     * Return a user by its unique id (UUID)
     *
     * @param string $uuid
     * @return QUI\Users\User|Nobody|SystemUser|false
     * @throws QUI\Users\Exception
     */
    public function getByUniqueId($uuid)
    {
        if (!$uuid || empty($uuid)) {
            return new Nobody();
        }

        if ($uuid == 5) {
            return new SystemUser();
        }

        if (isset($this->usersUUIDs[$uuid])) {
            return $this->get($this->usersUUIDs[$uuid]);
        }


        $result = QUI::getDataBase()->fetch(array(
            'select' => array('uuid', 'id'),
            'from'   => self::table(),
            'where'  => array(
                'uuid' => trim($uuid)
            ),
            'limit'  => 1
        ));

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        $userId = (int)$result[0]['id'];

        $this->usersUUIDs[$uuid] = $userId;

        return $this->get($userId);
    }

    /**
     * get the user by username
     *
     * @param string $username - Username
     *
     * @throws QUI\Users\Exception
     * @return QUI\Users\User
     */
    public function getUserByName($username)
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => self::table(),
            'where'  => array(
                'username' => $username
            ),
            'limit'  => 1
        ));

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
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => self::table(),
            'where'  => array(
                'email' => $email
            ),
            'limit'  => 1
        ));

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

        $result = QUI::getDataBase()->fetch(
            array(
                'select' => 'username',
                'from'   => self::table(),
                'where'  => array(
                    'username' => $username
                ),
                'limit'  => 1
            )
        );

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
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'email',
            'from'   => self::table(),
            'where'  => array(
                'email' => $email
            ),
            'limit'  => 1
        ));

        return isset($result[0]) ? true : false;
    }

    /**
     * @deprecated
     *
     * Generates a hash of a password
     *
     * @param string $pass
     * @param string $salt -> deprecated
     *
     * @return string
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
     */
    public function count($params = array())
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
     * @todo where params
     *
     * @param  array $params
     * @return array|integer
     *
     * @throws QUI\Database\Exception
     */
    protected function execSearch($params)
    {
        $PDO    = QUI::getDataBase()->getPDO();
        $params = Orthos::clearArray($params);

        $allowOrderFields = array(
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
        );

        $max   = 10;
        $start = 0;

        /**
         * SELECT
         */
        $query = 'SELECT * FROM ' . self::table();
        $binds = array();

        if (isset($params['count'])) {
            $query = 'SELECT COUNT( id ) AS count FROM ' . self::table();
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
            if (!isset($params['searchSettings']['filter'])) {
                $params['searchSettings']['filter'] = array();
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

            if (isset($filter['filter_group'])
                && !empty($filter['filter_group'])
            ) {
                $filter_group = true;
            }

            if (isset($filter['filter_groups_exclude'])
                && !empty($filter['filter_groups_exclude'])
            ) {
                $filter_groups_exclude = true;
            }

            if (isset($filter['filter_regdate_first'])
                && !empty($filter['filter_regdate_first'])
            ) {
                $filter_regdate_first = true;
            }

            if (isset($filter['filter_regdate_last'])
                && !empty($filter['filter_regdate_last'])
            ) {
                $filter_regdate_last = true;
            }


            // create the search
            if (empty($search)) {
                $query .= ' WHERE 1=1 ';
            } else {
                $query            .= ' WHERE (';
                $binds[':search'] = '%' . $search . '%';

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

                    $query .= ' ' . $field . ' LIKE :search OR ';
                }

                if (substr($query, -3) == 'OR ') {
                    $query = substr($query, 0, -3);
                }

                $query .= ') ';
            }


            // empty where, no search possible
            if (strpos($query, 'WHERE ()') !== false) {
                return array();
            }


            if ($filter_status) {
                $query            .= ' AND active = :active';
                $binds[':active'] = (int)$filter['filter_status'];
            }


            if ($filter_group) {
                $groups = explode(',', $filter['filter_group']);

                foreach ($groups as $groupId) {
                    if ((int)$groupId > 0) {
                        $query                 .= ' AND usergroup LIKE :' . $groupId . ' ';
                        $binds[':' . $groupId] = '%' . (int)$groupId . '%';
                    }
                }
            }

            if ($filter_groups_exclude) {
                foreach ($filter['filter_groups_exclude'] as $groupId) {
                    if ((int)$groupId > 0) {
                        $query                 .= ' AND usergroup NOT LIKE :' . $groupId . ' ';
                        $binds[':' . $groupId] = '%,' . (int)$groupId . ',%';
                    }
                }
            }

            if ($filter_regdate_first) {
                $query              .= ' AND regdate >= :firstreg ';
                $binds[':firstreg'] = QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_first'] . ' 00:00:00'
                );
            }


            if ($filter_regdate_last) {
                $query             .= " AND regdate <= :lastreg ";
                $binds[':lastreg'] = QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_last'] . ' 00:00:00'
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
                $Statement->bindValue($key, $value, \PDO::PARAM_INT);
            } else {
                $Statement->bindValue($key, $value, \PDO::PARAM_STR);
            }
        }

        try {
            $Statement->execute();
        } catch (\PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= print_r($query, true);

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
     * @throws QUI\Users\Exception
     */
    protected function newId()
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'MAX(id) AS id',
            'from'   => self::table(),
            'limit'  => 1
        ));

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
        return preg_replace('/[^a-zA-Z0-9-_äöüß@\.]/', '', $username);
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
        $Engine   = QUI::getTemplateManager()->getEngine(true);
        $packages = QUI::getPackageManager()->getInstalled();
        $extend   = '';

        foreach ($packages as $package) {
            $name    = $package['name'];
            $userXml = OPT_DIR . $name . '/user.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            $Document = XML::getDomFromXml($userXml);
            $Path     = new \DOMXPath($Document);

            $tabs = $Path->query("//user/profile/tab");

            /* @var $Tab \DOMElement */
            foreach ($tabs as $Tab) {
                $extend .= DOM::parseCategoryToHTML($Tab);
            }
        }

        $Engine->assign(array(
            'QUI'    => new QUI(),
            'extend' => $extend
        ));

        return $Engine->fetch(SYS_DIR . 'template/users/profile.html');
    }
}
