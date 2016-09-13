<?php

/**
 * This file contains \QUI\Users\Manager
 */
namespace QUI\Users;

use QUI;
use QUI\Utils\Security\Orthos;

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
    /**
     * @var QUI\Projects\Project (active internal project)
     */
    private $Project = false;

    /**
     * @var array - list of users (cache)
     */
    private $users = array();

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

        $DataBase->table()->addColumn(self::table(), array(
            'id'         => 'int(11)',
            'username'   => 'varchar(50)',
            'password'   => 'varchar(50)',
            'usergroup'  => 'text',
            'firstname'  => 'varchar(40)',
            'lastname'   => 'varchar(40)',
            'usertitle'  => 'varchar(40)',
            'birthday'   => 'varchar(12)',
            'email'      => 'varchar(50)',
            'active'     => 'int(1)',
            'regdate'    => 'int(11)',
            'lastvisit'  => 'int(11)',
            'su'         => 'tinyint(1)',
            'avatar'     => 'text',
            'extra'      => 'text NULL',
            'lang'       => 'varchar(2) NULL',
            'expire'     => 'TIMESTAMP NULL',
            'lastedit'   => 'TIMESTAMP NOT NULL',
            'shortcuts'  => 'varchar(5) NULL',
            'activation' => 'varchar(20) NULL',
            'referal'    => 'varchar(200) NULL',
            'user_agent' => 'text',
            'address'    => 'int(11)'
        ));

        // Patch
        $DataBase->getPDO()->exec(
            'ALTER TABLE `' . self::table()
            . '` CHANGE `birthday` `birthday` DATE NULL DEFAULT NULL'
        );

        // Addresses
        $DataBase->table()->addColumn(self::tableAddress(), array(
            'id'         => 'int(11)',
            'uid'        => 'int(11)',
            'salutation' => 'varchar(10)',
            'firstname'  => 'varchar(40)',
            'lastname'   => 'varchar(40)',
            'phone'      => 'text',
            'mail'       => 'text',
            'company'    => 'varchar(100)',
            'delivery'   => 'text',
            'street_no'  => 'text',
            'zip'        => 'text',
            'city'       => 'text',
            'country'    => 'text'
        ));

        $DataBase->table()->setIndex(self::tableAddress(), 'id');

        $DataBase->getPDO()->exec(
            'ALTER TABLE `' . self::tableAddress()
            . '` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT'
        );
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

        if ($User instanceof User) {
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
     *
     * @return QUI\Users\User
     * @throws QUI\Users\Exception
     */
    public function createChild($username = false)
    {
        $newid = $this->newId();

        if ($username) {
            if ($this->usernameExists($username)) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.lib.user.exist'
                    )
                );
            }

            $newname = $username;
        } else {
            $newname = 'Neuer Benutzer';
            $i       = 0;

            while ($this->usernameExists($newname)) {
                $newname = 'Neuer Benutzer (' . $i . ')';
                $i++;
            }
        }

        self::checkUsernameSigns($username);


        // Nur erlaubte Zeichen zu lassen
        //$newname
        QUI::getDataBase()->insert(
            self::table(),
            array(
                'id'       => $newid,
                'username' => $newname,
                'regdate'  => time()
            )
        );

        $User = $this->get($newid);

        // workspace
        $twoColumn = '[{
                "attributes": {
                    "resizeLimit": [],
                    "height": 775,
                    "width": 373,
                    "setting_toggle": true
                },
                "children": [
                    {
                        "attributes": {
                            "name": "projects-panel",
                            "icon": "fa fa-home",
                            "title": "Projects",
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false,
                            "height": 599
                        },
                        "type": "controls/projects/project/Panel",
                        "isOpen": true
                    },
                    {
                        "attributes": {
                            "title": "Bookmarks",
                            "icon": "fa fa-bookmark",
                            "footer": false,
                            "name": "qui-bookmarks",
                            "height": 300,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false
                        },
                        "type": "controls/desktop/panels/Bookmarks",
                        "bookmarks": [],
                        "isOpen": false
                    },
                    {
                        "attributes": {
                            "height": 100,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false
                        },
                        "type": "qui/controls/messages/Panel",
                        "isOpen": false
                    },
                    {
                        "attributes": {
                            "height": 100,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false,
                            "title": "Upload"
                        },
                        "type": "controls/upload/Manager",
                        "isOpen": false
                    },
                    {
                        "attributes": {
                            "title": "QUIQQER-Hilfe",
                            "icon": "fa fa-h-square",
                            "height": 100,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false
                        },
                        "type": "controls/desktop/panels/Help",
                        "isOpen": false
                    }
                ]
            },
            {
                "attributes": {
                    "resizeLimit": [],
                    "height": 775,
                    "width": 1244
                },
                "children": [
                    {
                        "attributes": {
                            "title": "My Panel 1",
                            "icon": "fa fa-heart",
                            "name": "tasks"
                        },
                        "type": "qui/controls/desktop/Tasks",
                        "bar": {
                            "attributes": {
                                "name": "qui-taskbar-issogpst",
                                "styles": {
                                    "bottom": 0,
                                    "left": 0,
                                    "position": "absolute"
                                }
                            },
                            "type": "qui/controls/taskbar/Bar",
                            "tasks": [
                                {
                                    "attributes": {
                                        "closeable": true,
                                        "dragable": true
                                    },
                                    "type": "qui/controls/taskbar/Task",
                                    "instance": {
                                        "attributes": {
                                            "closeButton": true,
                                            "collapsible": false,
                                            "height": 745,
                                            "dragable": true
                                        },
                                        "type": "controls/help/Dashboard"
                                    }
                                }
                            ]
                        },
                        "isOpen": true
                    }
                ]
            }
        ]';

        $threeColumn = '[{
                "attributes": {
                    "resizeLimit": [],
                    "height": 775,
                    "width": 329,
                    "setting_toggle": true
                },
                "children": [
                    {
                        "attributes": {
                            "name": "projects-panel",
                            "icon": "fa fa-home",
                            "title": "Projects",
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false,
                            "height": 731
                        },
                        "type": "controls/projects/project/Panel",
                        "isOpen": true
                    },
                    {
                        "attributes": {
                            "title": "Bookmarks",
                            "icon": "fa fa-bookmark",
                            "footer": false,
                            "name": "qui-bookmarks",
                            "height": 400,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false
                        },
                        "type": "controls/desktop/panels/Bookmarks",
                        "bookmarks": [],
                        "isOpen": false
                    }
                ]
            },
            {
                "attributes": {
                    "resizeLimit": [],
                    "height": 775,
                    "width": 984,
                    "setting_toggle": false
                },
                "children": [
                    {
                        "attributes": {
                            "title": "My Panel 1",
                            "icon": "fa fa-heart",
                            "name": "tasks"
                        },
                        "type": "qui/controls/desktop/Tasks",
                        "bar": {
                            "attributes": {
                                "name": "qui-taskbar-issogpue",
                                "styles": {
                                    "bottom": 0,
                                    "left": 0,
                                    "position": "absolute"
                                }
                            },
                            "type": "qui/controls/taskbar/Bar",
                            "tasks": [
                                {
                                    "attributes": {
                                        "closeable": true,
                                        "dragable": true
                                    },
                                    "type": "qui/controls/taskbar/Task",
                                    "instance": {
                                        "attributes": {
                                            "closeButton": true,
                                            "collapsible": false,
                                            "height": 745,
                                            "dragable": true
                                        },
                                        "type": "controls/help/Dashboard"
                                    }
                                }
                            ]
                        },
                        "isOpen": true
                    }
                ]
            },
            {
                "attributes": {
                    "resizeLimit": [],
                    "height": 775,
                    "width": 283,
                    "setting_toggle": true
                },
                "children": [
                    {
                        "attributes": {
                            "height": 687,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false
                        },
                        "type": "qui/controls/messages/Panel",
                        "isOpen": true
                    },
                    {
                        "attributes": {
                            "height": 300,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false,
                            "title": "Upload"
                        },
                        "type": "controls/upload/Manager",
                        "isOpen": false
                    },
                    {
                        "attributes": {
                            "title": "QUIQQER-Hilfe",
                            "icon": "fa fa-h-square",
                            "height": 400,
                            "collapsible": true,
                            "dragable": false,
                            "closeButton": false
                        },
                        "type": "controls/desktop/panels/Help",
                        "isOpen": false
                    }
                ]
            }
        ]';

        $newWorkspaceId = QUI\Workspace\Manager::addWorkspace(
            $User,
            '2 Spalten', // #locale
            $twoColumn,
            500,
            700
        );

        QUI\Workspace\Manager::addWorkspace(
            $User,
            '3 Spalten', // #locale
            $threeColumn,
            500,
            700
        );

        QUI\Workspace\Manager::setStandardWorkspace($User, $newWorkspaceId);

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
        $password = $this->genHash($params['password']);

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
     * Return the users authenticator
     *
     * @param string $username - username
     * @return QUI\Interfaces\Users\Auth
     *
     * @throws Exception
     */
    public function getAuthenticator($username)
    {
        // Authentifizierung
        $authType  = QUI::conf('auth', 'type');
        $authClass = $authType;

        if ($authType == 'standard') {
            $authClass = Auth::class;
        }

        if (!class_exists($authClass)) {
            QUI\System\Log::addError(
                'Authentication Type not found. Please check your config settings'
            );

            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail'),
                401
            );
        }

        $Auth       = new $authClass($username);
        $implements = class_implements($Auth);

        if (!isset($implements['QUI\Interfaces\Users\Auth'])) {
            QUI\System\Log::addError(
                'Authentication Type is not from Interface QUI\Interfaces\Users\Auth'
            );

            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail'),
                401
            );
        }

        return $Auth;
    }

    /**
     * Returns all userids
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
     * Loged in a user
     *
     * @param string $username - username
     * @param string $pass - password
     *
     * @return QUI\Users\User
     * @throws QUI\Users\Exception
     */
    public function login($username, $pass)
    {
        if (!is_string($username) || empty($username)) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.wrong.username.input'),
                401
            );
        }

        if (!is_string($pass) || empty($pass)) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.wrong.password.input'),
                401
            );
        }

        $username = Orthos::clear($username);

        if (function_exists('get_magic_quotes_gpc') && !get_magic_quotes_gpc()) {
            $username = addslashes($username);
            $pass     = addslashes($pass);
        }

        if (empty($pass)) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.no.password'),
                401
            );
        }

        // Authentifizierung
        $authType  = QUI::conf('auth', 'type');
        $authClass = $authType;

        if ($authType == 'standard') {
            $authClass = '\QUI\Users\Auth';
        }

        if (!class_exists($authClass)) {
            QUI\System\Log::addError(
                'Authentication Type not found. Please check your config settings'
            );

            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail'),
                401
            );
        }

        $Auth = $this->getAuthenticator($username);

        /* @var $Auth QUI\Interfaces\Users\Auth */
        if ($Auth->auth($pass) === false) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail'),
                401
            );
        }

        $userId = $Auth->getUserId();

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
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.user.not.found'),
                404
            );
        }

        if ($userData[0]['active'] == 0) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail.user.not.found'),
                401
            );
        }

        if ($userData[0]['expire']
            && $userData[0]['expire'] != '0000-00-00 00:00:00'
            && strtotime($userData[0]['expire']) < time()
        ) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.login.expire', array(
                    'expire' => $userData[0]['expire']
                ))
            );
        }


        $User        = $this->get($userId);
        $Groups      = $User->Group;
        $groupActive = false;

        foreach ($Groups as $Group) {
            /* @var $Group QUI\Groups\Group */
            if ($Group->getAttribute('active') == 1) {
                $groupActive = true;
            }
        }

        if ($groupActive === false) {
            throw new QUI\Users\Exception(
                array('quiqqer/system', 'exception.login.fail'),
                401
            );
        }

        // session
        QUI::getSession()->set('auth', 1);
        QUI::getSession()->set('uid', $userId);
        QUI::getSession()->set('secHash', $this->getSecHash());

        $useragent = '';

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }

        QUI::getDataBase()->update(
            self::table(),
            array(
                'lastvisit'  => time(),
                'user_agent' => $useragent,
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
        if (!QUI::getSession()->check()) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permission.session.expired'
                ),
                401
            );
        }

        if (!QUI::getSession()->get('uid')) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permission.session.expired'
                ),
                401
            );
        }

        $User = $this->get(QUI::getSession()->get('uid'));

        if (!$User->isActive()) {
            QUI::getSession()->destroy();

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.user.inactive'
                ),
                401
            );
        }

        // Mehrfachanmeldungen? Dann keine Prüfung
        if (QUI::conf('session', 'multible')) {
            return;
        }

        $sessionSecHash = QUI::getSession()->get('secHash');
        $secHash        = $this->getSecHash();
        $userSecHash    = $User->getAttribute('secHash');

        if ($sessionSecHash == $secHash && $userSecHash == $secHash) {
            return;
        }


        $message = $User->getLocale()->get(
            'quiqqer/system',
            'exception.session.expired.from.other'
        );

        QUI::getSession()->set('uid', 0);
        QUI::getSession()->getSymfonySession()->clear();
        QUI::getSession()->refresh();

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
     * get the user by username
     *
     * @param string $username - Username
     *
     * @throws QUI\Users\Exception
     * @return QUI\Users\User
     */
    public function getUserByName($username)
    {
        $result = QUI::getDataBase()->fetch(
            array(
                'select' => 'id',
                'from'   => self::table(),
                'where'  => array(
                    'username' => $username
                ),
                'limit'  => 1
            )
        );

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
        $result = QUI::getDataBase()->fetch(
            array(
                'select' => 'id',
                'from'   => self::table(),
                'where'  => array(
                    'email' => $email
                ),
                'limit'  => 1
            )
        );

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
        $result = QUI::getDataBase()->fetch(
            array(
                'select' => 'email',
                'from'   => self::table(),
                'where'  => array(
                    'email' => $email
                ),
                'limit'  => 1
            )
        );

        return isset($result[0]) ? true : false;
    }

    /**
     * Generates a hash of a password
     *
     * @param string $pass
     * @param string $salt (optional) - use specific salt for password generation [default: randomly generated]
     *
     * @return string
     */
    public static function genHash($pass, $salt = null)
    {
        if ($salt === null) {
            $randomBytes = openssl_random_pseudo_bytes(SALT_LENGTH);
            $salt        = mb_substr(bin2hex($randomBytes), 0, SALT_LENGTH);
        }

        return $salt . md5($salt . $pass);
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
        if (isset($params['where'])) {
            // $_fields['where'] = $params['where'];
        }

        // wenn nicht durchsucht wird dann gelöschte nutzer nicht anzeigen
        if (!isset($params['search'])) {
            // $_fields['where_relation']  = "`active` != '-1' ";
        }


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

            $filter_status        = false;
            $filter_group         = false;
            $filter_regdate_first = false;
            $filter_regdate_last  = false;

            // set the filters
            if (isset($filter['filter_status'])
                && $filter['filter_status'] != 'all'
            ) {
                $filter_status = true;
            }

            if (isset($filter['filter_group'])
                && !empty($filter['filter_group'])
            ) {
                $filter_group = true;
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
                $query .= ' WHERE (';
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
                $query .= ' AND active = :active';
                $binds[':active'] = (int)$filter['filter_status'];
            }


            if ($filter_group) {
                $groups = explode(',', $filter['filter_group']);

                foreach ($groups as $groupId) {
                    if ((int)$groupId > 0) {
                        $query .= ' AND usergroup LIKE :' . $groupId . ' ';
                        $binds[':' . $groupId] = '%' . (int)$groupId . '%';
                    }
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
     * Gibt eine neue Benutzer Id zwischen 100 und 1000000000 zurück
     *
     * @return integer
     * @throws QUI\Users\Exception
     */
    protected function newId()
    {
        $create = true;
        $newid  = false;

        while ($create) {
            srand(microtime() * 1000000);
            $newid = rand(100, 1000000000);

            $result = QUI::getDataBase()->fetch(
                array(
                    'from'  => self::table(),
                    'where' => array(
                        'id' => $newid
                    )
                )
            );

            if (isset($result[0]) && $result[0]['id']) {
                $create = true;
                continue;
            }

            $create = false;
        }

        if (!$newid) {
            throw new QUI\Users\Exception('Could not create new User-ID');
        }

        return $newid;
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
}
