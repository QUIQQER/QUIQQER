<?php

/**
 * This file contains \QUI\Users\User
 */

namespace QUI\Users;

use QUI;
use QUI\Utils\Security\Orthos as Orthos;

/**
 * A user
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event   onUserSave [ \QUI\Users\User ]
 * @event   onUserDelete [ \QUI\Users\User ]
 * @event   onUserLoad [ \QUI\Users\User ]
 * @event   onUserSetPassword [ \QUI\Users\User ]
 * @event   onUserDisable [ \QUI\Users\User ]
 * @event   onUserActivate [ \QUI\Users\User ]
 * @event   onUserDeactivate [ \QUI\Users\User ]
 */
class User implements QUI\Interfaces\Users\User
{
    /**
     * The groups in which the user is
     *
     * @var array|\QUI\Groups\Group
     */
    public $Group = array();

    /**
     * User locale object
     *
     * @var \QUI\Locale
     */
    public $Locale = null;

    /**
     * User ID
     *
     * @var Integer
     */
    protected $_id;

    /**
     * User groups
     *
     * @var array
     */
    protected $_groups;

    /**
     * Username
     *
     * @var string
     */
    protected $_name;

    /**
     * User lang
     *
     * @var string
     */
    protected $_lang = null;

    /**
     * Active status
     *
     * @var Integer
     */
    protected $_active = 0;

    /**
     * Delete status
     *
     * @var Integer
     */
    protected $_deleted = 0;

    /**
     * Super user flag
     *
     * @var boolean
     */
    protected $_su = false;

    /**
     * Admin flag
     *
     * @var boolean
     */
    protected $_admin = null;

    /**
     * Settings
     *
     * @var array
     */
    protected $_settings;

    /**
     * User manager
     *
     * @var \QUI\Users\Manager
     */
    protected $_Users;

    /**
     * Encrypted pass
     *
     * @var string
     */
    protected $_password;

    /**
     * Extra fields
     *
     * @var array
     */
    protected $_extra = array();

    /**
     * user plugins
     *
     * @var array
     */
    protected $_plugins = array();

    /**
     * User addresses
     *
     * @var array
     */
    protected $_address_list = array();

    /**
     * contructor
     *
     * @param Integer $id - ID of the user
     * @param \QUI\Users\Manager $Users - the user manager
     *
     * @throws \QUI\Exception
     */
    public function __construct($id, Manager $Users)
    {
        $id = (int)$id;

        if (!$id || $id <= 10) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.wrong.uid'
                ),
                404
            );
        }

        $this->_Users = $Users;
        $this->_id    = $id;

        $this->refresh();
    }

    /**
     * refresh the data from the database
     *
     * @throws QUI\Exception
     */
    public function refresh()
    {
        $data = QUI::getDataBase()->fetch(array(
            'from'  => Manager::Table(),
            'where' => array(
                'id' => $this->_id
            ),
            'limit' => '1'
        ));

        if (!isset($data[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.not.found'
                ),
                404
            );
        }

        // Eigenschaften setzen
        if (isset($data[0]['username'])) {
            $this->_name = $data[0]['username'];
            unset($data[0]['username']);
        }

        if (isset($data[0]['id'])) {
            $this->_id = $data[0]['id'];
            unset($data[0]['id']);
        }

        if (isset($data[0]['usergroup'])) {
            try {
                $this->setGroups($data[0]['usergroup']);

            } catch (QUI\Exception $Exception) {
                // nohting
            }

            unset($data[0]['usergroup']);
        }

        if (isset($data[0]['active']) && $data[0]['active'] == 1) {
            $this->_active = 1;
        }

        if ($data[0]['active'] == -1) {
            $this->_deleted = 1;
        }

        if (isset($data[0]['su']) && $data[0]['su'] == 1) {
            $this->_su = true;
        }

        if (isset($data[0]['password'])) {
            $this->_password = $data[0]['password'];
        }

        foreach ($data[0] as $key => $value) {
            if ($key == 'user_agent') {
                $this->_settings['user_agent'] = $value;
                continue;
            }

            $this->setAttribute($key, $value);
        }

        if ($this->getAttribute('expire') == '0000-00-00 00:00:00') {
            $this->setAttribute('expire', false);
        }


        // Extras
        if (isset($data[0]['extra'])) {
            $extraList = $this->_getListOfExtraAttributes();
            $extras    = array();
            $extraData = json_decode($data[0]['extra'], true);

            if (!is_array($extraData)) {
                $extraData = array();
            }

            foreach ($extraList as $attribute) {
                $extras[$attribute] = true;
            }

            foreach ($extraData as $attribute => $value) {
                if (isset($extras[$attribute])) {
                    $this->setAttribute($attribute, $extraData[$attribute]);
                }
            }
        }

        // Event
        QUI::getEvents()->fireEvent('userLoad', array($this));
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getPermission()
     *
     * @param string $right
     * @param array|boolean $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     * @return boolean
     */
    public function getPermission($right, $ruleset = false)
    {
        //@todo Benutzer muss erster prüfen ob bei ihm das recht seperat gesetzt ist

        return QUI::getPermissionManager()
            ->getUserPermission($this, $right, $ruleset);
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getType()
     */
    public function getType()
    {
        return get_class($this);
    }

    /**
     * (non-PHPdoc)
     *
     * @see        QUI\Interfaces\Users\User::getExtra()
     *
     * @param string $field
     *
     * @return string|Integer|array
     * @deprecated use getAttribute
     */
    public function getExtra($field)
    {
        return $this->getAttribute($field);
    }

    /**
     * (non-PHPdoc)
     *
     * @see        QUI\Interfaces\Users\User::setExtra()
     *
     * @param string $field
     * @param string|Integer|array $value
     *
     * @deprecated use user.xml and setAttribute
     */
    public function setExtra($field, $value)
    {
        $this->setAttribute($field, $value);
    }

    /**
     * (non-PHPdoc)
     *
     * @see        QUI\Interfaces\Users\User::loadExtra()
     *
     * @param QUI\Projects\Project $Project
     *
     * @todo       für projekte wieder realiseren, vorerst ausgeschaltet
     * @deprecated use user.xml
     * @return false
     */
    public function loadExtra(QUI\Projects\Project $Project)
    {
        return false;

        if (!file_exists(
            USR_DIR . 'lib/' . $Project->getAttribute('name') . '/User.php'
        )
        ) {
            return false;
        }

        if (!class_exists('UserExtend')) {
            require USR_DIR . 'lib/' . $Project->getAttribute('name') . '/User.php';
        }

        if (class_exists('UserExtend')) {
            $this->Extend = new UserExtend($this, $Project);

            return $this->Extend;
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getId()
     */
    public function getId()
    {
        return $this->_id ? $this->_id : false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getName()
     */
    public function getName()
    {
        $firstname = $this->getAttribute('firstname');
        $lastname  = $this->getAttribute('lastname');

        if ($firstname && $lastname) {
            return $firstname . ' ' . $lastname;
        }

        return $this->getUsername();
    }

    /**
     * Return username
     *
     * @return bool|string
     */
    public function getUsername()
    {
        return $this->_name ? $this->_name : false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getLang()
     */
    public function getLang()
    {
        if (!is_null($this->_lang)) {
            return $this->_lang;
        }

        $lang  = QUI::getLocale()->getCurrent();
        $langs = QUI::availableLanguages();

        if ($this->getAttribute('lang')) {
            $lang = $this->getAttribute('lang');
        }

        if (in_array($lang, $langs)) {
            $this->_lang = $lang;
        }

        // falls null, dann vom Projekt
        if (!$this->_lang) {
            try {
                $this->_lang = QUI\Projects\Manager::get()
                    ->getAttribute('lang');

            } catch (QUI\Exception $Exception) {

            }
        }

        // wird noch gebraucht?
        if (!$this->_lang) {
            $this->_lang = QUI::getLocale()->getCurrent();
        }

        return $this->_lang;
    }

    /**
     * (non-PHPdoc)
     *
     * @see iUser::getLocale()
     */
    public function getLocale()
    {
        if ($this->Locale) {
            return $this->Locale;
        }

        $this->Locale = new QUI\Locale();
        $this->Locale->setCurrent($this->getLang());

        return $this->Locale;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getStatus()
     */
    public function getStatus()
    {
        if ($this->_active) {
            return $this->_active;
        }

        return false;
    }

    /**
     * Return the user Currency
     *
     * @return string
     * @todo do it as a plugin
     */
    public function getCurrency()
    {
        if ($this->getAttribute('currency')) {
            if (QUI\Currency::existCurrency($this->getAttribute('currency'))) {
                return $this->getAttribute('currency');
            }
        }

        $Country = $this->getCountry();

        if ($Country) {
            $currency = $Country->getCurrencyCode();

            if (QUI\Currency::existCurrency($currency)) {
                return $currency;
            }
        }

        return QUI\Currency::getDefaultCurrency();
    }

    /**
     * Return the Country from the
     *
     * @return QUI\Countries\Country|boolean
     */
    public function getCountry()
    {
        try {
            $Standard = $this->getStandardAddress();

            if ($Standard) {
                $Country = $Standard->getCountry();

                return $Country;
            }

        } catch (QUI\Exception $Exception) {

        }

        // apache fallback falls möglich
        if (isset($_SERVER["GEOIP_COUNTRY_CODE"])) {
            try {
                return QUI\Countries\Manager::get(
                    $_SERVER["GEOIP_COUNTRY_CODE"]
                );

            } catch (QUI\Exception $Exception) {

            }
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::setGroups()
     *
     * @param array|string $groups
     */
    public function setGroups($groups)
    {
        if (empty($groups)) {
            return;
        }

        $Groups = QUI::getGroups();

        $this->Group   = array();
        $this->_groups = false;

        if (is_array($groups)) {
            $aTmp = array();

            foreach ($groups as $group) {
                $tg = $Groups->get($group);

                if ($tg) {
                    $this->Group[] = $tg;
                    $aTmp[]        = $group;
                }
            }

            $this->_groups = implode($aTmp, ',');
            return;
        }

        if (is_string($groups) && strpos($groups, ',') !== false) {
            $groups = explode(',', $groups);
            $aTmp   = array();

            foreach ($groups as $g) {
                if (empty($g)) {
                    continue;
                }

                try {
                    $this->Group[] = $Groups->get($g);
                    $aTmp[]        = $g;

                } catch (QUI\Exception $Exception) {
                    // nothing
                }
            }

            $this->_groups = ',' . implode($aTmp, ',') . ',';
            return;
        }


        if (is_string($groups)) {
            try {
                $this->Group[] = $Groups->get($groups);
                $this->_groups = ',' . $groups . ',';

            } catch (QUI\Exception $Exception) {

            }
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getGroups()
     *
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     *
     * @return array
     */
    public function getGroups($array = true)
    {
        if ($this->Group && is_array($this->Group)) {
            if ($array == true) {
                return $this->Group;
            }

            return $this->_groups;
        }

        return false;
    }

    /**
     * Remove a group from the user
     *
     * @param QUI\Groups\Group|Integer $Group
     */
    public function removeGroup($Group)
    {
        $Groups = QUI::getGroups();

        if (is_string($Group) || is_int($Group)) {
            $Group = $Groups->get((int)$Group);
        }

        $groups = $this->getGroups(true);
        $new_gr = array();

        if (!is_array($groups)) {
            $groups = array();
        }

        foreach ($groups as $key => $UserGroup) {
            /* @var $UserGroup QUI\Groups\Group */
            if ($UserGroup->getId() != $Group->getId()) {
                $new_gr[] = $UserGroup->getId();
            }
        }

        $this->setGroups($new_gr);
    }

    /**
     * Add the user to a group
     *
     * @param Integer $groupId
     */
    public function addToGroup($groupId)
    {
        try {
            $Groups = QUI::getGroups();
            $Group  = $Groups->get($groupId);

        } catch (QUI\Exception $Exception) {
            return;
        }

        $groups    = $this->getGroups(true);
        $newGroups = array();
        $_tmp      = array();

        if (!is_array($groups)) {
            $groups = array();
        }

        $groups[] = $Group;

        foreach ($groups as $key => $UserGroup) {
            /* @var $UserGroup QUI\Groups\Group */
            if (isset($_tmp[$UserGroup->getId()])) {
                continue;
            }

            $_tmp[$UserGroup->getId()] = true;

            $newGroups[] = $UserGroup->getId();
        }

        $this->setGroups($newGroups);
    }

    /**
     * @param Integer $gid
     * @deprecated use addToGroup
     */
    public function addGroup($gid)
    {
        $this->addToGroup($gid);
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::setAttribute()
     *
     * @param string $key
     * @param string|Integer|array $value
     *
     * @return void
     * @throws QUI\Exception
     */
    public function setAttribute($key, $value)
    {
        if (!$key || $key == 'id' || $key == 'password'
            || $key == 'user_agent'
        ) {
            return;
        }

        switch ($key) {
            case "su":
                $this->_su = (int)$value;
                break;

            case "username":
            case "name":
                // Falls der Name geändert wird muss geprüft werden das es diesen nicht schon gibt
                Manager::checkUsernameSigns($value);

                if ($this->_name != $value
                    && $this->_Users->usernameExists($value)
                ) {
                    throw new QUI\Exception('Name existiert bereits');
                }

                $this->_name = $value;
                break;

            case "usergroup":
                $this->setGroups($value);
                break;

            case "expire":
                $time = strtotime($value);

                if ($time > 0) {
                    $this->_settings[$key] = date('Y-m-d H:i:s', $time);
                }
                break;

            default:
                $this->_settings[$key] = $value;
                break;
        }
    }

    /**
     * Remove an attribute
     *
     * @param string $key
     */
    public function removeAttribute($key)
    {
        if (!$key || $key == 'id' || $key == 'password'
            || $key == 'user_agent'
        ) {
            return;
        }

        if (isset($this->_settings[$key])) {
            unset($this->_settings[$key]);
        }
    }

    /**
     * set attributes
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getAttribute()
     *
     * @param string $var
     *
     * @return string|Integer|array
     */
    public function getAttribute($var)
    {
        if (isset($this->_settings[$var])) {
            if ($var == 'avatar') {
                return URL_DIR . 'media/users/' . $this->_settings[$var];
            }

            return $this->_settings[$var];
        }

        return false;
    }

    /**
     * @deprecated use getAttributes
     */
    public function getAllAttributes()
    {
        return self::getAttributes();
    }

    /**
     * Return all user attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $params = $this->_settings;

        $params['id']      = $this->getId();
        $params['active']  = $this->_active;
        $params['deleted'] = $this->_deleted;
        $params['admin']   = $this->canUseBackend();
        $params['avatar']  = $this->getAvatar();
        $params['su']      = $this->isSU();

        $params['usergroup'] = $this->getGroups(false);
        $params['username']  = $this->getUsername();
        $params['extras']    = $this->_extra;

        return $params;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getAvatar()
     *
     * @param boolean $url - get the avatar with the complete url string
     *
     * @return string
     */
    public function getAvatar($url = false)
    {
        if (isset($this->_settings["avatar"])) {
            if ($url == true) {
                return URL_DIR . 'media/users/' . $this->_settings["avatar"];
            }

            return $this->_settings["avatar"];
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::logout()
     */
    public function logout()
    {
        if (!$this->getId()) {
            return;
        }

        // Wenn der Benutzer dieser hier ist
        $Users    = QUI::getUsers();
        $SessUser = $Users->getUserBySession();

        if ($SessUser->getId() == $this->getId()) {
            $Session = QUI::getSession();
            $Session->destroy();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::setPassword()
     *
     * @param string $new - new password
     * @param QUI\Interfaces\Users\User|boolean $ParentUser
     *
     * @throws QUI\Exception
     */
    public function setPassword($new, $ParentUser = false)
    {
        $this->_checkRights($ParentUser);

        if (empty($new)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.empty.password'
                )
            );
        }

        QUI::getEvents()->fireEvent('userSetPassword', array($this));


        $newpass         = Manager::genHash($new);
        $this->_password = $newpass;

        QUI::getDataBase()->update(
            Manager::Table(),
            array('password' => $newpass),
            array('id' => $this->getId())
        );

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.password.save.success'
            )
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::checkPassword()
     *
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     *
     * @return boolean
     */
    public function checkPassword($pass, $encrypted = false)
    {
        if (!$encrypted) {
            $_pw = $this->_Users->genHash($pass);
        } else {
            $_pw = $pass;
        }

        return $_pw == $this->_password ? true : false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::activate()
     *
     * @param string|boolean $code - activasion code [optional]
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public function activate($code = false)
    {
        if ($code == false) {
            $this->_checkRights();
        }

        // benutzer ist schon aktiv, aktivierung kann nicht durchgeführt werden
        if ($this->isActive()) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.activasion.user.is.activated'
                )
            );
        }

        if ($code && $code != $this->getAttribute('activation')) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.activasion.wrong.code'
                )
            );
        }

        $groups = $this->getGroups(false);

        if (empty($groups)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.activasion.no.groups'
                )
            );
        }

        if ($this->_password == '') {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.activasion.no.password'
                )
            );
        }

        QUI::getDataBase()->update(
            Manager::Table(),
            array('active' => 1),
            array('id' => $this->getId())
        );

        $this->_active = true;

        try {

            QUI::getEvents()->fireEvent('userActivate', array($this));

        } catch (QUI\Exception $Exception) {

            QUI\System\Log::addError($Exception->getMessage(), array(
                'UserId'        => $this->getId(),
                'ExceptionType' => $Exception->getType()
            ));
        }

        return $this->_active;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::deactivate()
     */
    public function deactivate()
    {
        $this->_checkRights();
        $this->_canBeDeleted();

        QUI::getEvents()->fireEvent('userDeactivate', array($this));

        QUI::getDataBase()->update(
            Manager::Table(),
            array('active' => 0),
            array('id' => $this->getId())
        );

        $this->_active = false;
        $this->logout();

        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::disable()
     *
     * @param QUI\Interfaces\Users\User|boolean $ParentUser
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public function disable($ParentUser = false)
    {
        $this->_checkRights($ParentUser);
        $this->_canBeDeleted();

        QUI::getEvents()->fireEvent('userDisable', array($this));

        QUI::getDataBase()->update(
            Manager::Table(),
            array(
                'username'   => '',
                'active'     => -1,
                'password'   => '',
                'usergroup'  => '',
                'firstname'  => '',
                'lastname'   => '',
                'usertitle'  => '',
                'birthday'   => '',
                'email'      => '',
                'su'         => 0,
                'avatar'     => '',
                'extra'      => '',
                'lang'       => '',
                'shortcuts'  => '',
                'activation' => '',
                'expire'     => '0000-00-00 00:00:00'
            ),
            array('id' => $this->getId())
        );

        $this->logout();

        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::save()
     *
     * @param QUI\Interfaces\Users\User|boolean $ParentUser
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public function save($ParentUser = false)
    {
        $this->_checkRights($ParentUser);

        $expire   = '0000-00-00 00:00:00';
        $birthday = '0000-00-00';

        if ($this->getAttribute('expire')) {
            // Datumsprüfung auf Syntax
            $value = trim($this->getAttribute('expire'));

            if (Orthos::checkMySqlDatetimeSyntax($value)) {
                $expire = $value;
            }
        }

        if ($this->getAttribute('birthday')) {
            // Datumsprüfung auf Syntax
            $value = trim($this->getAttribute('birthday'));

            if (strlen($value) == 10) {
                $value .= ' 00:00:00';
            }

            if (Orthos::checkMySqlDatetimeSyntax($value)) {
                $birthday = substr($value, 0, 10);
            }
        }

        // Pluginerweiterungen - onSave Event
        $extra      = array();
        $attributes = $this->_getListOfExtraAttributes();

        foreach ($attributes as $attribute) {
            $extra[$attribute] = $this->getAttribute($attribute);
        }

        QUI::getEvents()->fireEvent('userSave', array($this));


        // add to everone
        $Everyone = new QUI\Groups\Everyone();
        $this->addToGroup($Everyone->getId());


        return QUI::getDataBase()->update(
            Manager::Table(),
            array(
                'username'  => $this->getUsername(),
                'usergroup' => $this->getGroups(false),
                'firstname' => $this->getAttribute('firstname'),
                'lastname'  => $this->getAttribute('lastname'),
                'usertitle' => $this->getAttribute('usertitle'),
                'birthday'  => $birthday,
                'email'     => $this->getAttribute('email'),
                'avatar'    => $this->getAvatar(),
                'su'        => $this->isSU(),
                'extra'     => json_encode($extra),
                'lang'      => $this->getAttribute('lang'),
                'lastedit'  => date("Y-m-d H:i:s"),
                'expire'    => $expire,
                'shortcuts' => $this->getAttribute('shortcuts'),
                'address'   => (int)$this->getAttribute('address')
            ),
            array('id' => $this->getId())
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::isSU()
     */
    public function isSU()
    {
        if ($this->_su == true) {
            return true;
        }

        return false;
    }

    /**
     * @deprecated
     */
    public function isAdmin()
    {
        return $this->canUseBackend();
    }

    /**
     * @return boolean
     */
    public function canUseBackend()
    {
        if (!is_null($this->_admin)) {
            return $this->_admin;
        }

        $this->_admin = QUI\Rights\Permission::isAdmin();

        return $this->_admin;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::isDeleted()
     */
    public function isDeleted()
    {
        return $this->_deleted;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::isActive()
     */
    public function isActive()
    {
        return $this->_active;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::isOnline()
     */
    public function isOnline()
    {
        return QUI::getSession()->isUserOnline($this->getId());
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::delete()
     */
    public function delete()
    {
        $this->_canBeDeleted();

        // Pluginerweiterungen - onDelete Event
        QUI::getEvents()->fireEvent('userDelete', array($this));

        QUI::getDataBase()->delete(
            Manager::Table(),
            array('id' => $this->getId())
        );

        $this->logout();

        return true;
    }

    /**
     * Checks the edit rights of a user
     *
     * @param QUI\Users\User|boolean $ParentUser
     *
     * @return boolean - true
     * @throws QUI\Exception
     */
    protected function _checkRights($ParentUser = false)
    {
        $Users       = QUI::getUsers();
        $SessionUser = $Users->getUserBySession();

        if ($ParentUser && $ParentUser->getType() == 'QUI\\Users\\SystemUser') {
            return true;
        }

        if ($SessionUser->isSU()) {
            return true;
        }

        if ($SessionUser->getId() == $SessionUser->getId()) {
            return true;
        }

        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/system',
                'exception.lib.user.no.edit.rights'
            )
        );
    }

    /**
     * Return the list which extra attributes exist
     * Plugins could extend the user attributes
     * look at https://dev.quiqqer.com/quiqqer/quiqqer/wikis/User-Xml
     *
     * @return array
     */
    protected function _getListOfExtraAttributes()
    {
        try {
            return QUI\Cache\Manager::get('user/plugin-attribute-list');

        } catch (QUI\Exception $Exception) {

        }

        $list       = QUI::getPackageManager()->getInstalled();
        $attributes = array();

        foreach ($list as $entry) {
            $plugin  = $entry['name'];
            $userXml = OPT_DIR . $plugin . '/user.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            $attributes = array_merge(
                $attributes,
                $this->_readAttributesFromUserXML($userXml)
            );
        }

        $attributes = array_merge(
            $attributes,
            $this->_readAttributesFromUserXML(SYS_DIR . 'user.xml')
        );

        QUI\Cache\Manager::set('user/plugin-attribute-list', $attributes);

        return $attributes;
    }

    /**
     * Read an user.xml and return the attributes,
     * if some extra attributes defined
     *
     * @param string $file
     *
     * @return array
     */
    protected function _readAttributesFromUserXML($file)
    {
        $Dom  = QUI\Utils\XML::getDomFromXml($file);
        $Attr = $Dom->getElementsByTagName('attributes');

        if (!$Attr->length) {
            return array();
        }

        /* @var $Attributes \DOMElement */
        $Attributes = $Attr->item(0);
        $list       = $Attributes->getElementsByTagName('attribute');

        if (!$list->length) {
            return array();
        }

        $attributes = array();

        for ($c = 0; $c < $list->length; $c++) {
            $Attribute = $list->item($c);

            if ($Attribute->nodeName == '#text') {
                continue;
            }

            $attributes[] = trim($Attribute->nodeValue);
        }

        return $attributes;
    }

    /**
     * Add a address to the user
     *
     * @param array $params
     *
     * @return QUI\Users\Address
     */
    public function addAddress($params = array())
    {
        $_params = array();
        $needles = array(
            'salutation',
            'firstname',
            'lastname',
            'phone',
            'mail',
            'company',
            'delivery',
            'street_no',
            'zip',
            'city',
            'country'
        );

        if (!is_array($params)) {
            $params = array();
        }

        foreach ($needles as $needle) {
            if (!isset($params[$needle])) {
                $_params[$needle] = '';
                continue;
            }

            if (is_array($params[$needle])) {
                $_params[$needle] = json_encode(
                    Orthos::clearArray($params[$needle])
                );

                continue;
            }

            $_params[$needle] = Orthos::clear($params[$needle]);
        }

        $tmp_first = $this->getAttribute('firstname');
        $tmp_last  = $this->getAttribute('lastname');

        if (empty($tmp_first) && empty($tmp_last)) {
            $this->setAttribute('firstname', $_params['firstname']);
            $this->setAttribute('lastname', $_params['lastname']);
            $this->save();
        }


        $_params['uid'] = $this->getId();

        QUI::getDataBase()->insert(
            Manager::TableAddress(),
            $_params
        );

        return $this->getAddress(
            QUI::getDataBase()->getPDO()->lastInsertId()
        );
    }

    /**
     * Returns all addresses from the user
     *
     * @return array
     */
    public function getAddressList()
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'   => Manager::TableAddress(),
            'select' => 'id',
            'where'  => array(
                'uid' => $this->getId()
            )
        ));

        if (!isset($result[0])) {
            return array();
        }

        $list = array();

        foreach ($result as $entry) {
            $id        = (int)$entry['id'];
            $list[$id] = $this->getAddress($id);
        }

        return $list;
    }

    /**
     * Get a address from the user
     *
     * @param Integer $id - address ID
     *
     * @return QUI\Users\Address
     */
    public function getAddress($id)
    {
        $id = (int)$id;

        if (isset($this->_address_list[$id])) {
            return $this->_address_list[$id];
        }

        $this->_address_list[$id] = new QUI\Users\Address($this, $id);

        return $this->_address_list[$id];
    }

    /**
     * Return the standard address from the user
     * If no standard address set, the first address will be returned
     *
     * @throws QUI\Exception
     * @return QUI\Users\Address|false
     */
    public function getStandardAddress()
    {
        if ($this->getAttribute('address')) {
            return $this->getAddress($this->getAttribute('address'));
        }

        $list = $this->getAddressList();

        if (count($list)) {
            reset($list);

            return current($list);
        }

        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/system',
                'exception.user.no.address.exists'
            )
        );
    }

    /**
     * Could the user be deleted?
     *
     * @throws QUI\Exception
     */
    protected function _canBeDeleted()
    {
        if ($this->isSU()) {
            $suUsers = QUI::getUsers()->getUserIds(array(
                'where' => array(
                    'active' => 1,
                    'su'     => 1
                )
            ));

            if (count($suUsers) <= 1) {
                throw new QUI\Exception(
                    'User cant be destroyed or deactivated. At least it must be one super user exist in the system.'
                );
            }
        }

        // check if the user is the only active one in the system
        // if it is so, no the user cant be deleted
        $activeUsers = QUI::getUsers()->getUserIds(array(
            'where' => array(
                'active' => 1
            )
        ));

        if (count($activeUsers) <= 1) {
            throw new QUI\Exception(
                'User cant be destroyed or deactivated. At least it must be one user exist in the system.'
            );
        }
    }
}
