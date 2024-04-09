<?php

/**
 * This file contains \QUI\Users\User
 */

namespace QUI\Users;

use QUI;
use QUI\ERP\Currency\Handler as Currencies;
use QUI\Utils\Security\Orthos as Orthos;

use function array_flip;
use function array_merge;
use function array_search;
use function class_exists;
use function count;
use function date;
use function explode;
use function file_exists;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_null;
use function is_numeric;
use function is_string;
use function json_decode;
use function md5;
use function reset;
use function strpos;
use function strtotime;
use function trim;

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
 * @event   onUserExtraAttributes [ \QUI\Users\User ]
 */
class User implements QUI\Interfaces\Users\User
{
    /**
     * The groups in which the user is
     *
     * @var QUI\Groups\Group[]|null
     */
    public ?array $Group = null;

    /**
     * User locale object
     *
     * @var QUI\Locale|null
     */
    public ?QUI\Locale $Locale = null;

    /**
     * User ID
     *
     * @var integer|null
     */
    protected ?int $id = null;

    /**
     * User UUID
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * User groups
     *
     * @var string
     */
    protected string $groups;

    /**
     * Username
     *
     * @var string
     */
    protected string $name;

    /**
     * User lang
     *
     * @var string|null
     */
    protected ?string $lang = null;

    /**
     * Active status
     *
     * @var integer
     */
    protected int $active = 0;

    /**
     * Delete status
     *
     * @var integer
     */
    protected int $deleted = 0;

    /**
     * Super user flag
     *
     * @var boolean
     */
    protected bool $su = false;

    /**
     * Admin flag
     *
     * @var boolean
     */
    protected ?bool $admin = null;

    /**
     * is the user a company
     *
     * @var false
     */
    protected bool $company = false;

    /**
     * @var array
     */
    protected array $authenticator = [];

    /**
     * Settings
     *
     * @var array
     */
    protected array $settings;

    /**
     * User manager
     *
     * @var \QUI\Users\Manager
     */
    protected $Users;

    /**
     * Encrypted pass
     *
     * @var string
     */
    protected $password;

    /**
     * Extra fields
     *
     * @var array
     */
    protected $extra = [];

    /**
     * user plugins
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * User addresses
     *
     * @var array
     */
    protected $address_list = [];

    /**
     * @var null|Address
     */
    protected $StandardAddress = null;

    /**
     * construct loading flag
     *
     * @var bool
     */
    protected $isLoaded = true;

    /**
     * constructor
     *
     * @param integer $id - ID of the user
     * @param \QUI\Users\Manager $Users - the user manager
     *
     * @throws \QUI\Users\Exception
     */
    public function __construct($id, Manager $Users)
    {
        $this->isLoaded = false;
        $this->Users = $Users;

        if (is_numeric($id)) {
            $id = (int)$id;

            if (!$id || $id <= 10) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'exception.lib.user.wrong.uid'
                    ),
                    404
                );
            }

            $this->id = $id;
        } else {
            $this->uuid = $id;
        }

        $this->refresh();
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
     * @see QUI\Interfaces\Users\User::getLang()
     */
    public function getLang()
    {
        if ($this->lang !== null) {
            return $this->lang;
        }

        if (
            $this->getId() === QUI::getUserBySession()->getId()
            && QUI::getSession()->get('quiqqer-user-language')
        ) {
            $this->lang = QUI::getSession()->get('quiqqer-user-language');

            return $this->lang;
        }

        $lang = QUI::getLocale()->getCurrent();
        $langs = QUI::availableLanguages();

        if ($this->getAttribute('lang')) {
            $lang = $this->getAttribute('lang');
        }

        if (in_array($lang, $langs)) {
            $this->lang = $lang;
        }

        // falls null, dann vom Projekt
        if (!$this->lang) {
            try {
                $this->lang = QUI\Projects\Manager::get()->getAttribute('lang');
            } catch (QUI\Exception) {
            }
        }

        // wird noch gebraucht?
        if (!$this->lang) {
            $this->lang = QUI::getLocale()->getCurrent();
        }

        return $this->lang;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getId()
     */
    public function getId()
    {
        return $this->id ?: false;
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $var
     *
     * @return string|integer|array
     * @see QUI\Interfaces\Users\User::getAttribute()
     *
     */
    public function getAttribute($var)
    {
        return $this->settings[$var] ?? false;
    }

    /**
     * refresh the data from the database
     *
     * @throws QUI\Users\Exception
     */
    public function refresh()
    {
        if ($this->uuid !== null) {
            $data = QUI::getDataBase()->fetch([
                'from' => Manager::table(),
                'where' => [
                    'uuid' => $this->uuid
                ],
                'limit' => 1
            ]);
        } else {
            $data = QUI::getDataBase()->fetch([
                'from' => Manager::table(),
                'where' => [
                    'id' => $this->id
                ],
                'limit' => 1
            ]);
        }

        if (!isset($data[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.not.found'
                ),
                404
            );
        }

        // Eigenschaften setzen
        $this->uuid = $data[0]['uuid'];
        $this->id = (int)$data[0]['id'];


        if (isset($data[0]['username'])) {
            $this->name = $data[0]['username'];
            unset($data[0]['username']);
        }

        if (isset($data[0]['usergroup'])) {
            $this->groups = $data[0]['usergroup'];
            //$this->setGroups($data[0]['usergroup']);

            unset($data[0]['usergroup']);
        } else {
            $this->groups = '';
        }

        if (isset($data[0]['active']) && $data[0]['active'] == 1) {
            $this->active = 1;
        }

        if ($data[0]['active'] == -1) {
            $this->deleted = 1;
            $this->active = -1;
        }

        if (isset($data[0]['su']) && $data[0]['su'] == 1) {
            $this->su = true;
        }

        if (isset($data[0]['password'])) {
            $this->password = $data[0]['password'];
        }

        foreach ($data[0] as $key => $value) {
            if ($key == 'user_agent') {
                $this->settings['user_agent'] = $value;
                continue;
            }

            $this->setAttribute($key, $value);
        }

        if (isset($data[0]['company'])) {
            $this->company = (bool)$data[0]['company'];
        }

        if ($this->getAttribute('expire') == '0000-00-00 00:00:00') {
            $this->setAttribute('expire', false);
        }


        if (isset($data[0]['extra'])) {
            $extraList = $this->getListOfExtraAttributes();
            $extras = [];
            $extraData = json_decode($data[0]['extra'], true);

            if (!is_array($extraData)) {
                $extraData = [];
            }

            foreach ($extraList as $entry) {
                $extras[$entry['name']] = $entry;
            }

            foreach ($extraData as $attribute => $value) {
                if (!isset($extras[$attribute])) {
                    continue;
                }

                if (isset($extras[$attribute]['encrypt']) && $extras[$attribute]['encrypt']) {
                    $this->setAttribute(
                        $attribute,
                        QUI\Security\Encryption::decrypt($extraData[$attribute])
                    );

                    continue;
                }

                $this->setAttribute($attribute, $extraData[$attribute]);
            }
        }

        if (isset($data[0]['authenticator'])) {
            $this->authenticator = json_decode($data[0]['authenticator'], true);

            if (!is_array($this->authenticator)) {
                $this->authenticator = [];
            }
        }

        // load default address fields
        // syn main user address fields
        $this->isLoaded = true;
        $this->setAttribute('firstname', $data[0]['firstname']);
        $this->setAttribute('lastname', $data[0]['lastname']);
        $this->setAttribute('email', $data[0]['email']);

        // Event
        QUI::getEvents()->fireEvent('userLoad', [$this]);
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $key
     * @param string|integer|array $value
     *
     * @return void
     * @throws QUI\Exception
     * @see QUI\Interfaces\Users\User::setAttribute()
     *
     */
    public function setAttribute($key, $value)
    {
        if (!$key || $key == 'id' || $key == 'password' || $key == 'user_agent') {
            return;
        }

        switch ($key) {
            case "su":
                // only a super user can set a superuser
                if (QUI::getUsers()->existsSession() && QUI::getUsers()->getUserBySession()->isSU()) {
                    if (is_numeric($value)) {
                        $this->su = (bool)(int)$value;
                    } else {
                        $this->su = (bool)$value;
                    }
                }
                break;

            case "username":
            case "name":
                $value = QUI::getUsers()->clearUsername($value);

                // Falls der Name geändert wird muss geprüft werden das es diesen nicht schon gibt
                Manager::checkUsernameSigns($value);

                if ($this->name != $value && QUI::getUsers()->usernameExists($value)) {
                    throw new QUI\Users\Exception(
                        QUI::getLocale()->get('quiqqer/quiqqer', 'exception.user.name.already.exists')
                    );
                }

                $this->name = $value;
                break;

            case "usergroup":
                $this->setGroups($value);
                break;

            case "expire":
                if ($value) {
                    $time = strtotime($value);

                    if ($time > 0) {
                        $this->settings[$key] = date('Y-m-d H:i:s', $time);
                    }
                }
                break;

            case "lang":
                $this->lang = $value;
                $this->settings[$key] = $value;
                break;

            case "address":
                $this->StandardAddress = null;
                $this->settings[$key] = $value;
                break;

            case "email":
                if ($value === null) {
                    $value = '';
                }

                $value = trim($value);
                $this->settings['email'] = $value;

                if ($this->isLoaded === true) {
                    try {
                        $this->getStandardAddress()->editMail(0, $value);
                    } catch (QUI\Exception) {
                        if (empty($value)) {
                            $this->getStandardAddress()->clearMail();
                        }
                    }
                }
                break;

            case "firstname":
                $this->settings['firstname'] = $value;

                if ($this->isLoaded === true) {
                    $this->getStandardAddress()->setAttribute('firstname', $value);
                }
                break;

            case "lastname":
                $this->settings['lastname'] = $value;

                if ($this->isLoaded === true) {
                    $this->getStandardAddress()->setAttribute('lastname', $value);
                }
                break;

            default:
                $this->settings[$key] = $value;
                break;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::isSU()
     */
    public function isSU()
    {
        return $this->su === true;
    }

    /**
     * Return the standard address from the user
     * If no standard address set, the first address will be returned
     *
     * @return QUI\Users\Address|false
     * @throws QUI\Users\Exception
     */
    public function getStandardAddress()
    {
        $Address = $this->getStandardAddressHelper();
        $mailList = $Address->getMailList();
        $email = $this->getAttribute('email');
        $email = trim($email);

        // set default mail address
        if (empty($mailList) && !empty($email)) {
            try {
                $Address->addMail($email);
            } catch (\Exception $Exception) {
                QUI\System\Log::addInfo($Exception->getMessage(), [
                    'email' => $email
                ]);
            }
        }

        return $Address;
    }

    /**
     * @return Address
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    protected function getStandardAddressHelper(): Address
    {
        if ($this->StandardAddress) {
            return $this->StandardAddress;
        }

        if ($this->getAttribute('address')) {
            try {
                $this->StandardAddress = $this->getAddress($this->getAttribute('address'));

                return $this->StandardAddress;
            } catch (QUI\Exception) {
            }
        }

        $list = $this->getAddressList();

        if (count($list)) {
            reset($list);

            $this->StandardAddress = \current($list);

            return $this->StandardAddress;
        }

        $Address = $this->addAddress([
            'firstname' => $this->getAttribute('firstname'),
            'lastname' => $this->getAttribute('lastname')
        ], QUI::getUsers()->getSystemUser());

        if (!empty($this->getAttribute('email'))) {
            $Address->addMail($this->getAttribute('email'));
        }

        $Address->save(QUI::getUsers()->getSystemUser());

        return $Address;
    }

    /**
     * Get a address from the user
     *
     * @param integer $id - address ID
     * @return QUI\Users\Address
     *
     * @throws \QUI\Users\Exception
     */
    public function getAddress($id)
    {
        $id = (int)$id;

        if (isset($this->address_list[$id])) {
            return $this->address_list[$id];
        }

        $this->address_list[$id] = new QUI\Users\Address($this, $id);

        return $this->address_list[$id];
    }

    /**
     * Returns all addresses from the user
     *
     * @return array
     */
    public function getAddressList()
    {
        $result = QUI::getDataBase()->fetch([
            'from' => Manager::tableAddress(),
            'select' => 'id',
            'where' => [
                'userUuid' => $this->getUniqueId()
            ]
        ]);

        if (!isset($result[0])) {
            return [];
        }

        $list = [];

        foreach ($result as $entry) {
            $id = (int)$entry['id'];

            try {
                $list[$id] = $this->getAddress($id);
            } catch (QUI\Users\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $list;
    }

    /**
     * Add a address to the user
     *
     * @param array $params
     * @param QUI\Interfaces\Users\User $ParentUser - Edit user [default: Session user]
     *
     * @return QUI\Users\Address
     *
     * @throws Exception
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function addAddress($params = [], $ParentUser = null)
    {
        if (is_null($ParentUser)) {
            $ParentUser = QUI::getUserBySession();
        }

        $this->checkEditPermission($ParentUser);

        // check max limit of user address
        $addresses = QUI::getDataBase()->fetch([
            'count' => 'count',
            'from' => Manager::tableAddress(),
            'where' => [
                'userUuid' => $this->getUniqueId()
            ]
        ]);

        // max 100 addresses per user
        // @todo do it as permission
        if (!empty($addresses[0]['count']) && $addresses[0]['count'] > 100) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.too.many.addresses'
            ]);
        }


        $_params = [];
        $needles = [
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
        ];

        if (!is_array($params)) {
            $params = [];
        }

        foreach ($needles as $needle) {
            if (!isset($params[$needle])) {
                $_params[$needle] = '';
                continue;
            }

            if (is_array($params[$needle])) {
                $_params[$needle] = \json_encode(
                    Orthos::clearArray($params[$needle])
                );

                continue;
            }

            $_params[$needle] = Orthos::clear($params[$needle]);
        }

        $_params['uid'] = $this->getId();
        $_params['userUuid'] = $this->getUniqueId();
        $_params['uuid'] = QUI\Utils\Uuid::get();

        QUI::getDataBase()->insert(
            Manager::tableAddress(),
            $_params
        );

        $CreatedAddress = $this->getAddress(
            QUI::getDataBase()->getPDO()->lastInsertId()
        );

        $tmp_first = $this->getAttribute('firstname');
        $tmp_last = $this->getAttribute('lastname');

        if (empty($tmp_first) && empty($tmp_last)) {
            $this->setAttribute('firstname', $_params['firstname']);
            $this->setAttribute('lastname', $_params['lastname']);
            $this->save($ParentUser);
        }

        if (count($this->getAddressList()) === 1) {
            $this->setAttribute('address', $CreatedAddress->getUuid());
            $this->save($ParentUser);
        }

        return $CreatedAddress;
    }

    /**
     * Checks the edit permissions
     * Can the user be edited by the current user?
     *
     * @param QUI\Users\User|boolean $ParentUser
     *
     * @return boolean - true
     * @throws QUI\Permissions\Exception
     */
    public function checkEditPermission($ParentUser = false)
    {
        $Users = QUI::getUsers();
        $SessionUser = $Users->getUserBySession();

        if ($ParentUser && $ParentUser->getType() == SystemUser::class) {
            return true;
        }

        if ($SessionUser->isSU()) {
            return true;
        }

        if ($SessionUser->getId() == $this->getId()) {
            return true;
        }

        $hasPermission = QUI\Permissions\Permission::hasPermission(
            'quiqqer.admin.users.edit',
            $SessionUser
        );

        if ($hasPermission) {
            return true;
        }


        throw new QUI\Permissions\Exception(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'exception.lib.user.no.edit.rights'
            )
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getType()
     */
    public function getType()
    {
        return $this::class;
    }

    /**
     * Exists the permission in the user permissions
     *
     * @param string $permission
     *
     * @return boolean|string
     */
    public function hasPermission($permission)
    {
        $list = QUI::getPermissionManager()->getUserPermissionData($this);

        return $list[$permission] ?? false;
    }

    /**
     * (non-PHPdoc)
     *
     * @param QUI\Interfaces\Users\User|boolean $ParentUser
     *
     * @throws QUI\Exception
     * @see QUI\Interfaces\Users\User::save()
     *
     */
    public function save($ParentUser = false)
    {
        $this->checkEditPermission($ParentUser);

        $expire = '0000-00-00 00:00:00';
        $birthday = '0000-00-00';

        QUI::getEvents()->fireEvent('userSaveBegin', [$this]);

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

            if (\strlen($value) == 10) {
                $value .= ' 00:00:00';
            }

            if (Orthos::checkMySqlDatetimeSyntax($value)) {
                $birthday = \substr($value, 0, 10);
            }
        }

        $avatar = '';

        if (
            $this->getAttribute('avatar')
            && QUI\Projects\Media\Utils::isMediaUrl($this->getAttribute('avatar'))
        ) {
            $avatar = $this->getAttribute('avatar');
        }

        // Pluginerweiterungen - onSave Event
        $extra = [];
        $attributes = $this->getListOfExtraAttributes();

        foreach ($attributes as $entry) {
            $attribute = $entry['name'];

            if (isset($entry['encrypt']) && $entry['encrypt']) {
                $extra[$attribute] = QUI\Security\Encryption::encrypt(
                    $this->getAttribute($attribute)
                );
                continue;
            }

            $extra[$attribute] = $this->getAttribute($attribute);
        }

        QUI::getEvents()->fireEvent('userSave', [$this]);

        // add to everyone
        $Everyone = new QUI\Groups\Everyone();
        $this->addToGroup($Everyone->getId());

        // check assigned toolbars
        $assignedToolbars = '';
        $toolbar = '';

        if ($this->getAttribute('assigned_toolbar')) {
            $toolbars = explode(',', $this->getAttribute('assigned_toolbar'));

            $assignedToolbars = \array_filter($toolbars, function ($toolbar) {
                return QUI\Editor\Manager::existsToolbar($toolbar);
            });

            $assignedToolbars = implode(',', $assignedToolbars);
        }

        if (QUI\Editor\Manager::existsToolbar($this->getAttribute('toolbar'))) {
            $toolbar = $this->getAttribute('toolbar');
        }

        if ($expire === '0000-00-00 00:00:00') {
            $expire = null;
        }

        if ($birthday === '0000-00-00') {
            $birthday = null;
        }

        // check if duplicated emails are exists
        if (QUI::conf('globals', 'emaillogin')) {
            $this->checkUserMail();
        }

        // check if su exists
        // check if one super user exists
        if (!$this->isSU()) {
            $superUsers = QUI::getUsers()->getUsers([
                'where' => [
                    'su' => 1,
                    'id' => [
                        'type' => 'NOT',
                        'value' => $this->getId()
                    ]
                ],
                'limit' => 1
            ]);

            if (!isset($superUsers[0])) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.user.save.one.superuser.must.exists')
                );
            }
        }

        // default address filling
        $email = trim($this->getAttribute('email'));
        $this->getStandardAddress();

        if (!$this->getAttribute('address')) {
            $this->setAttribute('address', $this->getStandardAddress()->getId());
        }


        // saving
        QUI::getDataBase()->update(
            Manager::table(),
            [
                'username' => $this->getUsername(),
                'usergroup' => ',' . implode(',', $this->getGroups(false)) . ',',
                'firstname' => $this->getAttribute('firstname'),
                'lastname' => $this->getAttribute('lastname'),
                'usertitle' => $this->getAttribute('usertitle'),
                'birthday' => $birthday,
                'email' => $email,
                'avatar' => $avatar,
                'su' => $this->isSU() ? 1 : 0,
                'extra' => \json_encode($extra),
                'lang' => $this->getAttribute('lang'),
                'lastedit' => date("Y-m-d H:i:s"),
                'expire' => $expire,
                'shortcuts' => $this->getAttribute('shortcuts'),
                'address' => !empty($this->getAttribute('address')) ? $this->getAttribute('address') : null,
                'company' => $this->isCompany() ? 1 : 0,
                'toolbar' => $toolbar,
                'assigned_toolbar' => $assignedToolbars,
                'authenticator' => \json_encode($this->authenticator),
                'lastLoginAttempt' => $this->getAttribute('lastLoginAttempt') ?: null,
                'failedLogins' => $this->getAttribute('failedLogins') ?: 0
            ],
            ['id' => $this->getId()]
        );

        $this->getStandardAddress()->save($ParentUser);

        QUI::getEvents()->fireEvent('userSaveEnd', [$this]);

        QUI\Workspace\Menu::clearMenuCache($this);
    }

    /**
     * Return the list which extra attributes exist
     * Plugins could extend the user attributes
     * look at https://dev.quiqqer.com/quiqqer/quiqqer/wikis/User-Xml
     *
     * @return array
     */
    public function getListOfExtraAttributes(): array
    {
        $cache = 'quiqqer/users/user-extra-attributes';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $list = QUI::getPackageManager()->getInstalled();
        $attributes = [];

        foreach ($list as $entry) {
            $plugin = $entry['name'];
            $userXml = OPT_DIR . $plugin . '/user.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            $attributes = array_merge(
                $attributes,
                $this->readAttributesFromUserXML($userXml)
            );
        }

        try {
            QUI::getEvents()->fireEvent('userExtraAttributes', [$this, &$attributes]);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        QUI\Cache\Manager::set($cache, $attributes);

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
    protected function readAttributesFromUserXML($file): array
    {
        $cache = 'quiqqer/users/user-extra-attributes/' . md5($file);

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
        }

        $Dom = QUI\Utils\Text\XML::getDomFromXml($file);
        $Attr = $Dom->getElementsByTagName('attributes');

        if (!$Attr->length) {
            return [];
        }

        /* @var $Attributes \DOMElement */
        $Attributes = $Attr->item(0);
        $list = $Attributes->getElementsByTagName('attribute');

        if (!$list->length) {
            return [];
        }

        $attributes = [];

        for ($c = 0; $c < $list->length; $c++) {
            $Attribute = $list->item($c);

            if ($Attribute->nodeName == '#text') {
                continue;
            }

            $attributes[] = [
                'name' => trim($Attribute->nodeValue),
                'encrypt' => (bool)$Attribute->getAttribute('encrypt'),
                'no-auto-save' => (bool)$Attribute->getAttribute('no-auto-save')
            ];
        }

        QUI\Cache\Manager::set($cache, $attributes);

        return $attributes;
    }

    /**
     * Add the user to a group
     *
     * @param integer $groupId
     */
    public function addToGroup($groupId)
    {
        try {
            $Groups = QUI::getGroups();
            $Group = $Groups->get($groupId);
        } catch (QUI\Exception) {
            return;
        }

        $groups = $this->getGroups(true);
        $newGroups = [];
        $_tmp = [];

        if (!is_array($groups)) {
            $groups = [];
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
     * (non-PHPdoc)
     *
     * @param boolean $asObjects - returns the groups as objects (true) or as an array (false)
     *
     * @return QUI\Groups\Group[]|array|bool
     * @see QUI\Interfaces\Users\User::getGroups()
     */
    public function getGroups($asObjects = true)
    {
        if ($asObjects === true) {
            if ($this->Group === null) {
                $this->Group = [];
                $groupIds = explode(',', trim($this->groups, ','));

                foreach ($groupIds as $id) {
                    try {
                        $this->Group[] = QUI::getGroups()->get($id);
                    } catch (QUI\Exception) {
                    }
                }
            }

            return $this->Group;
        }

        if (!empty($this->groups)) {
            return explode(',', trim($this->groups, ','));
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @param array|string $groups
     * @see QUI\Interfaces\Users\User::setGroups()
     */
    public function setGroups($groups)
    {
        if (empty($groups)) {
            return;
        }

        $Groups = QUI::getGroups();

        $this->Group = [];
        $this->groups = '';

        if (is_array($groups)) {
            $aTmp = [];
            $this->Group = [];

            foreach ($groups as $group) {
                $tg = $Groups->get($group);

                if ($tg) {
                    $this->Group[] = $tg;
                    $aTmp[] = $group;
                }
            }

            $this->groups = ',' . implode(',', $aTmp) . ',';

            return;
        }

        if (is_string($groups) && strpos($groups, ',') !== false) {
            $groups = explode(',', $groups);
            $aTmp = [];

            foreach ($groups as $g) {
                if (empty($g)) {
                    continue;
                }

                try {
                    $this->Group[] = $Groups->get($g);
                    $aTmp[] = $g;
                } catch (QUI\Exception) {
                    // nothing
                }
            }

            $this->groups = ',' . implode(',', $aTmp) . ',';

            return;
        }


        if (is_string($groups)) {
            try {
                $this->Group[] = $Groups->get($groups);
                $this->groups = ',' . $groups . ',';
            } catch (QUI\Exception) {
            }
        }
    }

    /**
     * @throws QUI\Users\Exception
     */
    protected function checkUserMail()
    {
        // check if duplicated emails are exists
        try {
            $email = $this->getAttribute('email');

            if (!empty($email)) {
                $found = QUI::getDataBase()->fetch([
                    'from' => Manager::table(),
                    'where' => [
                        'email' => $email,
                        'id' => [
                            'value' => $this->getId(),
                            'type' => 'NOT'
                        ]
                    ],
                    'limit' => 1
                ]);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.user.save.mail.exists')
            );
        }

        if (isset($found[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.user.save.mail.exists')
            );
        }
    }

    /**
     * Return username
     *
     * @return bool|string
     */
    public function getUsername()
    {
        return $this->name ?: false;
    }

    /**
     * Is the user a company?
     *
     * @return false
     */
    public function isCompany()
    {
        return $this->company;
    }

    /**
     * Return the authenticators from the user
     *
     * @return array
     */
    public function getAuthenticators()
    {
        $result = [];

        $available = Auth\Handler::getInstance()->getAvailableAuthenticators();
        $available = array_flip($available);

        foreach ($this->authenticator as $authenticator) {
            if (!Auth\Helper::hasUserPermissionToUseAuthenticator($this, $authenticator)) {
                continue;
            }

            if (isset($available[$authenticator])) {
                $result[] = new $authenticator($this->getUsername());
            }
        }

        return $result;
    }

    /**
     * Enables an authenticator for the user
     *
     * @param string $authenticator - Name of the authenticator
     * @param QUI\Interfaces\Users\User|boolean $ParentUser - optional, the saving user, default = session user
     * @throws QUI\Users\Exception
     */
    public function enableAuthenticator($authenticator, $ParentUser = false)
    {
        $available = Auth\Handler::getInstance()->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator])) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.authenticator.not.found'],
                404
            );
        }

        if (!Auth\Helper::hasUserPermissionToUseAuthenticator($this, $authenticator)) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.authenticator.not.found'],
                404
            );
        }

        if (in_array($authenticator, $this->authenticator)) {
            return;
        }

        if (class_exists('QUI\Watcher')) {
            QUI\Watcher::addString(
                QUI::getLocale()->get('quiqqer/quiqqer', 'user.enable.authenticator', [
                    'id' => $this->getId()
                ]),
                '',
                ['authenticator' => $authenticator]
            );
        }

        $this->authenticator[] = $authenticator;
        $this->save($ParentUser);
    }

    /**
     * Disables an authenticator from the user
     *
     * @param $authenticator
     * @param QUI\Interfaces\Users\User|boolean $ParentUser - optional, the saving user, default = session user
     *
     * @throws Exception
     */
    public function disableAuthenticator($authenticator, $ParentUser = false)
    {
        $available = Auth\Handler::getInstance()->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator])) {
            throw new QUI\Users\Exception(
                [
                    'quiqqer/quiqqer',
                    'exception.authenticator.not.found'
                ],
                404
            );
        }

        if (!in_array($authenticator, $this->authenticator)) {
            return;
        }

        if (($key = array_search($authenticator, $this->authenticator)) !== false) {
            unset($this->authenticator[$key]);
        }

        if (class_exists('QUI\Watcher')) {
            QUI\Watcher::addString(
                QUI::getLocale()->get('quiqqer/quiqqer', 'user.disable.authenticator', [
                    'id' => $this->getId()
                ]),
                '',
                [
                    'authenticator' => $authenticator
                ]
            );
        }

        $this->save($ParentUser);
    }

    /**
     * Is the wanted authenticator enabled for the user?
     *
     * @param string $authenticator - name of the authenticator
     * @return bool
     */
    public function hasAuthenticator($authenticator)
    {
        if (!Auth\Helper::hasUserPermissionToUseAuthenticator($this, $authenticator)) {
            return false;
        }

        return in_array($authenticator, $this->authenticator);
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $right
     * @param array|boolean $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     * @return bool|int|string
     * @see QUI\Interfaces\Users\User::getPermission()
     *
     */
    public function getPermission($right, $ruleset = false): bool|int|string
    {
        //@todo Benutzer muss erster prüfen ob bei ihm das recht seperat gesetzt ist

        return QUI::getPermissionManager()->getUserPermission($this, $right, $ruleset);
    }

    /**
     * Return the unique id for the user
     *
     * @return string
     */
    public function getUniqueId()
    {
        return $this->uuid ?: '';
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getStatus()
     */
    public function getStatus()
    {
        if ($this->active) {
            return $this->active;
        }

        return false;
    }

    /**
     * Return the user Currency
     *
     * @return string
     * @todo do it as a plugin
     */
    public function getCurrency(): string
    {
        try {
            QUI::getPackage('quiqqer/currency');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_ALERT);

            return 'EUR';
        }


        if ($this->getAttribute('currency')) {
            if (Currencies::existCurrency($this->getAttribute('currency'))) {
                return $this->getAttribute('currency');
            }
        }

        $Country = $this->getCountry();

        if ($Country) {
            $currency = $Country->getCurrencyCode();

            if (Currencies::existCurrency($currency)) {
                return $currency;
            }
        }

        return Currencies::getDefaultCurrency()->getCode();
    }

    /**
     * Return the Country from the
     *
     * @return QUI\Countries\Country|boolean
     */
    public function getCountry()
    {
        try {
            $Address = $this->getCurrentAddress();

            if ($Address instanceof Address) {
                return $Address->getCountry();
            }
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Standard = $this->getStandardAddress();

            if ($Standard) {
                return $Standard->getCountry();
            }
        } catch (QUI\Exception) {
        }

        // apache fallback falls möglich
        if (isset($_SERVER["GEOIP_COUNTRY_CODE"])) {
            try {
                return QUI\Countries\Manager::get(
                    $_SERVER["GEOIP_COUNTRY_CODE"]
                );
            } catch (QUI\Exception) {
            }
        }

        return false;
    }

    /**
     * Return the current instance address
     * -> Standard Address, Delivery Address or Invoice Address
     *
     * @return Address
     * @throws Exception
     */
    public function getCurrentAddress()
    {
        $CurrentAddress = $this->getAttribute('CurrentAddress');

        if ($CurrentAddress instanceof Address) {
            return $CurrentAddress;
        }

        return $this->getStandardAddress();
    }

    /**
     * Clear all groups of user
     *
     * @return void
     */
    public function clearGroups()
    {
        $this->Group = [];
        $this->groups = '';
    }

    /**
     * Remove a group from the user
     *
     * @param QUI\Groups\Group|integer $Group
     */
    public function removeGroup($Group)
    {
        $Groups = QUI::getGroups();

        if (is_string($Group) || is_int($Group)) {
            $Group = $Groups->get((int)$Group);
        }

        $groups = $this->getGroups();
        $new_gr = [];

        if (!is_array($groups)) {
            $groups = [];
        }

        foreach ($groups as $UserGroup) {
            /* @var $UserGroup QUI\Groups\Group */
            if ($UserGroup->getId() != $Group->getId()) {
                $new_gr[] = $UserGroup->getId();
            }
        }

        $this->setGroups($new_gr);
    }

    /**
     * @param integer $gid
     * @deprecated use addToGroup
     */
    public function addGroup($gid)
    {
        $this->addToGroup($gid);
    }

    /**
     * Remove an attribute
     *
     * @param string $key
     */
    public function removeAttribute($key)
    {
        if (!$key || $key == 'id' || $key == 'password' || $key == 'user_agent') {
            return;
        }

        if (isset($this->settings[$key])) {
            unset($this->settings[$key]);
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
        $params = $this->settings;
        $params['id'] = $this->getId();
        $params['active'] = $this->active;
        $params['deleted'] = $this->deleted;
        $params['admin'] = $this->canUseBackend();
        $params['su'] = $this->isSU();

        $params['usergroup'] = $this->getGroups(false);
        $params['username'] = $this->getUsername();
        $params['extras'] = $this->extra;
        $params['hasPassword'] = empty($this->password) ? 0 : 1;
        $params['avatar'] = '';
        $params['displayName'] = $this->getDisplayName();
        $params['companyName'] = '';

        if ($this->isCompany()) {
            try {
                $Address = $this->getStandardAddress();
                $addressCompany = $Address->getAttribute('company');

                if (!empty($addressCompany)) {
                    $params['companyName'] = $addressCompany;
                }
            } catch (\Exception) {
            }
        }

        try {
            $Image = QUI\Projects\Media\Utils::getImageByUrl($this->getAttribute('avatar'));

            $params['avatar'] = $Image->getUrl();
        } catch (QUI\Exception) {
        }

        return $params;
    }

    /**
     * @return boolean
     */
    public function canUseBackend()
    {
        if ($this->admin !== null) {
            return $this->admin;
        }

        $this->admin = QUI\Permissions\Permission::isAdmin();

        return $this->admin;
    }

    /**
     * @deprecated
     */
    public function isAdmin()
    {
        return $this->canUseBackend();
    }

    /**
     * Get display name of the user.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        // Name directly set in user attributes
        $firstname = $this->getAttribute('firstname');
        $lastname = $this->getAttribute('lastname');

        if ($firstname && $lastname) {
            return $firstname . ' ' . $lastname;
        }

        if ($firstname) {
            return $firstname;
        }

        if ($lastname) {
            return $lastname;
        }

        // Use standard address
        try {
            $Address = $this->getStandardAddress();
            $addressName = $Address->getName();
            $addressCompany = $Address->getAttribute('company');

            if (!empty($addressCompany)) {
                return $addressCompany;
            }

            if (!empty($addressName)) {
                return $addressName;
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $this->getUsername();
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::getName()
     */
    public function getName()
    {
        $firstname = $this->getAttribute('firstname');
        $lastname = $this->getAttribute('lastname');

        if ($firstname && $lastname) {
            return $firstname . ' ' . $lastname;
        }

        return $this->getUsername();
    }

    /**
     * (non-PHPdoc)
     *
     * @return QUI\Interfaces\Projects\Media\File|false
     * @see QUI\Interfaces\Users\User::getAvatar()
     *
     */
    public function getAvatar()
    {
        $result = QUI::getEvents()->fireEvent('userGetAvatar', [$this]);

        foreach ($result as $Entry) {
            if ($Entry instanceof QUI\Interfaces\Projects\Media\File) {
                return $Entry;
            }
        }

        $avatar = $this->getAttribute('avatar');

        if (!QUI\Projects\Media\Utils::isMediaUrl($avatar)) {
            $Project = QUI::getProjectManager()->getStandard();
            $Media = $Project->getMedia();

            return $Media->getPlaceholderImage();
        }

        try {
            return QUI\Projects\Media\Utils::getImageByUrl($avatar);
        } catch (QUI\Exception) {
        }

        $Project = QUI::getProjectManager()->getStandard();
        $Media = $Project->getMedia();

        return $Media->getPlaceholderImage();
    }

    /**
     * This method can be used, for change the user password by himself
     *
     * @param string $newPassword
     * @param string $oldPassword
     * @param bool|QUI\Interfaces\Users\User $ParentUser
     * @throws QUI\Users\Exception
     */
    public function changePassword($newPassword, $oldPassword, $ParentUser = false)
    {
        $this->checkEditPermission($ParentUser);

        $newPassword = trim($newPassword);
        $oldPassword = trim($oldPassword);

        if (empty($newPassword) || empty($oldPassword)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.empty.password'
                )
            );
        }

        if (!$this->checkPassword($oldPassword)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.user.oldPassword.is.wrong'
                )
            );
        }

        QUI::getEvents()->fireEvent(
            'userChangePasswordBefore',
            [$this, $newPassword, $oldPassword]
        );

        $this->updatePassword($newPassword);

        QUI::getEvents()->fireEvent(
            'userChangePassword',
            [$this, $newPassword, $oldPassword]
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $password - Password
     * @param boolean $encrypted - is the given password already encrypted?
     *
     * @return boolean
     * @see QUI\Interfaces\Users\User::checkPassword()
     *
     */
    public function checkPassword($password, $encrypted = false)
    {
        if ($encrypted) {
            return $password == $this->password ? true : false;
        }

        try {
            $Auth = Auth\Handler::getInstance()->getAuthenticator(
                Auth\QUIQQER::class,
                $this->getUsername()
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());

            return false;
        }

        try {
            $Auth->auth([
                'password' => $password
            ]);

            return true;
        } catch (QUI\Users\Exception) {
            // 401 -> wrong password
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return false;
    }

    /**
     * Return the authenticators from the user
     *
     * @param string $authenticator - Name of the authenticator
     * @return AuthenticatorInterface
     *
     * @throws QUI\Users\Exception
     */
    public function getAuthenticator($authenticator)
    {
        $Handler = Auth\Handler::getInstance();
        $available = $Handler->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator])) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.authenticator.not.found'],
                404
            );
        }

        if (!in_array($authenticator, $this->authenticator)) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.authenticator.not.found'],
                404
            );
        }

        if (!Auth\Helper::hasUserPermissionToUseAuthenticator($this, $authenticator)) {
            throw new QUI\Users\Exception(
                ['quiqqer/quiqqer', 'exception.authenticator.not.found'],
                404
            );
        }

        return new $authenticator($this->getUsername());
    }

    /**
     * Update password to the database
     *
     * @param string $password
     */
    protected function updatePassword($password)
    {
        $newPassword = QUI\Security\Password::generateHash($password);
        $this->password = $newPassword;

        QUI::getDataBase()->update(
            Manager::table(),
            ['password' => $newPassword],
            ['id' => $this->getId()]
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $new - new password
     * @param QUI\Interfaces\Users\User|boolean $ParentUser
     *
     * @throws QUI\Users\Exception
     * @see QUI\Interfaces\Users\User::setPassword()
     *
     */
    public function setPassword($new, $ParentUser = false)
    {
        $this->checkEditPermission($ParentUser);

        if (empty($new)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.empty.password'
                )
            );
        }

        QUI::getEvents()->fireEvent('userSetPassword', [$this]);

        $this->updatePassword($new);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'message.password.save.success'
            )
        );
    }

    /**
     * @param string|boolean $code - activasion code [optional]
     * @param null|QUI\Interfaces\Users\User $ParentUser - optional, execution user
     *
     * @return boolean
     *
     * @throws \QUI\Users\Exception
     * @throws \QUI\Permissions\Exception
     *
     * @see QUI\Interfaces\Users\User::activate()
     *
     */
    public function activate($code = false, $ParentUser = null)
    {
        if ($code == false) {
            $this->checkEditPermission($ParentUser);
        }

        QUI::getEvents()->fireEvent('userActivateBegin', [$this, $code, $ParentUser]);


        // benutzer ist schon aktiv, aktivierung kann nicht durchgeführt werden
        if ($this->isActive()) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.activasion.user.is.activated'
                )
            );
        }

        if ($code && $code != $this->getAttribute('activation')) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.activation.wrong.code'
                )
            );
        }

        // check if is the users e-mail address already exists
        if (QUI::conf('globals', 'emaillogin')) {
            $this->checkUserMail();
        }

        $groups = $this->getGroups(false);

        if (empty($groups)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.activation.no.groups'
                )
            );
        }

        if ($this->password == '') {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.activation.no.password'
                )
            );
        }

        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 1],
            ['id' => $this->getId()]
        );

        $this->active = true;

        try {
            QUI::getEvents()->fireEvent('userActivate', [$this]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), [
                'UserId' => $this->getId(),
                'ExceptionType' => $Exception->getType()
            ]);
        }

        return $this->active;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::isActive()
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * (non-PHPdoc)
     *
     * @param User $ParentUser (optional) - Executing User
     * @return bool
     * @see QUI\Interfaces\Users\User::deactivate()
     */
    public function deactivate($ParentUser = null)
    {
        $this->checkEditPermission($ParentUser);
        $this->canBeDeleted();

        QUI::getEvents()->fireEvent('userDeactivate', [$this]);

        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 0],
            ['id' => $this->getId()]
        );

        $this->active = false;
        $this->logout();

        return true;
    }

    /**
     * Could the user be deleted?
     *
     * @return bool
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    protected function canBeDeleted()
    {
        // wenn benutzer deaktiviert ist, fällt die prüfung weg, da er bereits deaktiviert ist
        if (!$this->isActive()) {
            return true;
        }

        $SessionUser = QUI::getUserBySession();

        if (QUI::getUsers()->isSystemUser($SessionUser)) {
            return true;
        }

        // SuperUser can only be deleted by other SuperUsers
        if (!$SessionUser->isSU() && $this->isSU()) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.superuser_cannot_delete_himself')
            );
        }

        // Check if user can delete himself
        if (QUI::getUserBySession()->getId() === $this->getId()) {
            $this->checkPermission('quiqqer.users.delete_self');
        }

        // Check if user is the last SuperUser in the system
        if ($this->isSU()) {
            $suUsers = QUI::getUsers()->getUserIds([
                'where' => [
                    'active' => 1,
                    'su' => 1
                ],
                'limit' => 2
            ]);

            if (count($suUsers) <= 1) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.user.one.superuser.must.exists')
                );
            }
        }

        // check if the user is the only active one in the system
        // if it is so, no the user cant be deleted
        $activeUsers = QUI::getUsers()->getUserIds([
            'where' => [
                'active' => 1
            ],
            'limit' => 2
        ]);

        if (count($activeUsers) <= 1) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.user.one.active.user.must.exists')
            );
        }

        return true;
    }

    /**
     * @param $permission
     *
     * @return void
     *
     * @throws \QUI\Permissions\Exception
     */
    public function checkPermission($permission)
    {
        QUI\Permissions\Permission::checkPermission($permission, $this);
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

        QUI::getEvents()->fireEvent('userLogoutBegin', [$this]);

        // Wenn der Benutzer dieser hier ist
        $Users = QUI::getUsers();
        $SessUser = $Users->getUserBySession();

        if ($SessUser->getId() == $this->getId()) {
            $Session = QUI::getSession();
            $Session->destroy();
        }

        QUI::getEvents()->fireEvent('userLogout', [$this]);
    }

    /**
     * (non-PHPdoc)
     *
     * @param QUI\Interfaces\Users\User|boolean $ParentUser
     *
     * @return boolean
     * @throws QUI\Exception
     * @see QUI\Interfaces\Users\User::disable()
     *
     */
    public function disable($ParentUser = false)
    {
        $this->checkEditPermission($ParentUser);
        $this->canBeDeleted();

        QUI::getEvents()->fireEvent('userDisable', [$this]);

        $SessionUser = QUI::getUserBySession();
        $addresses = $this->getAddressList();

        /** @var Address $Address */
        foreach ($addresses as $Address) {
            $Address->delete();
        }

        $addresses = $this->getAddressList();

        /** @var Address $Address */
        foreach ($addresses as $Address) {
            $Address->delete();
        }

        QUI::getDataBase()->update(
            Manager::table(),
            [
                'username' => '',
                'active' => -1,
                'password' => '',
                'usergroup' => '',
                'firstname' => '',
                'lastname' => '',
                'usertitle' => '',
                'birthday' => null,
                'email' => '',
                'su' => 0,
                'avatar' => '',
                'extra' => '',
                'lang' => '',
                'shortcuts' => '',
                'activation' => '',
                'expire' => null
            ],
            ['id' => $this->getId()]
        );

        $this->logout();

        QUI\System\Log::write(
            'User disabled.',
            QUI\System\Log::LEVEL_INFO,
            [
                'deletedUserId' => $this->getId(),
                'deletedUsername' => $this->getUsername(),
                'executeUserId' => $SessionUser->getId(),
                'executeUsername' => $SessionUser->getUsername()
            ],
            'user'
        );

        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @param QUI\Interfaces\Users\User $PermissionUser (optional)
     * @return true
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     *
     * @see QUI\Interfaces\Users\User::delete()
     */
    public function delete($PermissionUser = null)
    {
        if (empty($PermissionUser)) {
            $PermissionUser = QUI::getUserBySession();
        }

        $this->checkDeletePermission($PermissionUser);

        // Pluginerweiterungen - onDelete Event
        QUI::getEvents()->fireEvent('userDelete', [$this]);

        QUI::getDataBase()->delete(
            Manager::table(),
            ['id' => $this->getId()]
        );

        // delete all workspaces of this user
        QUI::getDataBase()->delete(
            QUI\Workspace\Manager::table(),
            ['uid' => $this->getId()]
        );


        $this->logout();

        QUI\System\Log::write(
            'User deleted.',
            QUI\System\Log::LEVEL_INFO,
            [
                'deletedUserId' => $this->getId(),
                'deletedUsername' => $this->getUsername(),
                'executeUserId' => $PermissionUser->getId(),
                'executeUsername' => $PermissionUser->getUsername()
            ],
            'user'
        );

        return true;
    }

    /**
     * Checks the delete permissions
     * Can the user be deleted by the current user?
     *
     * @param QUI\Users\User|boolean $ParentUser
     *
     * @return boolean - true
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     */
    public function checkDeletePermission($ParentUser = false)
    {
        $this->canBeDeleted();

        $Users = QUI::getUsers();
        $SessionUser = $Users->getUserBySession();

        if ($ParentUser && $ParentUser->getType() == SystemUser::class) {
            return true;
        }

        if ($SessionUser->isSU()) {
            return true;
        }

        if ($SessionUser->getId() == $this->getId()) {
            return true;
        }

        $hasPermission = QUI\Permissions\Permission::hasPermission(
            'quiqqer.admin.users.delete',
            $SessionUser
        );

        if ($hasPermission) {
            return true;
        }


        throw new QUI\Permissions\Exception(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'exception.lib.user.no.delete.permission'
            )
        );
    }

    /**
     * @param integer $groupId
     * @return boolean
     */
    public function isInGroup($groupId)
    {
        $groups = $this->getGroups(false);

        if (!is_array($groups)) {
            return false;
        }

        return in_array($groupId, $groups);
    }

    /**
     * Set the company status, whether the use is a company or not
     *
     * @param bool $status - true ot false
     */
    public function setCompanyStatus($status = false)
    {
        if (is_bool($status)) {
            $this->company = $status;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Users\User::isDeleted()
     */
    public function isDeleted()
    {
        return $this->deleted;
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
}
