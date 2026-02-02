<?php

/**
 * This file contains \QUI\Users\User
 */

namespace QUI\Users;

use DOMElement;
use QUI;
use QUI\Database\Exception;
use QUI\ERP\Currency\Handler as Currencies;
use QUI\ExceptionStack;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User as QUIUserInterface;
use QUI\Utils\Security\Orthos as Orthos;
use QUI\Users\Attribute\AttributeVerificationStatus;
use QUI\Users\Attribute\Verifiable\AbstractVerifiableUserAttribute;

use function array_filter;
use function array_flip;
use function array_merge;
use function array_search;
use function class_exists;
use function count;
use function current;
use function date;
use function explode;
use function file_exists;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_null;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function md5;
use function reset;
use function strlen;
use function strtotime;
use function substr;
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
class User implements QUIUserInterface
{
    /**
     * The groups in which the user is
     *
     * @var QUI\Groups\Group[]|null
     */
    public ?array $Group = null;

    public ?QUI\Locale $Locale = null;

    protected ?int $id = null;

    protected ?string $uuid = null;

    protected string $groups;

    protected string $name;

    protected ?string $lang = null;

    protected int $active = 0;

    protected int $deleted = 0;

    /**
     * Super user flag
     */
    protected bool $su = false;

    /**
     * Admin flag
     */
    protected ?bool $admin = null;

    /**
     * is the user a company
     */
    protected bool $company = false;

    protected array $authenticator = [];

    protected array $settings;

    /**
     * Encrypted pass
     */
    protected string $password;

    /**
     * Extra fields
     */
    protected array $extra = [];

    /**
     * user plugins
     */
    protected array $plugins = [];

    /**
     * User addresses
     */
    protected array $address_list = [];

    protected ?Address $StandardAddress = null;

    /**
     * construct loading flag
     */
    protected bool $isLoaded = true;

    /**
     * verifiable status of the attributes
     */
    protected null | QUI\Users\Attribute\VerifiableUserAttributeCollection $verifiableAttributes = null;

    /**
     * @param int|string $id - ID of the user
     *
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws Exception
     */
    public function __construct(int | string $id)
    {
        $this->isLoaded = false;

        if (is_numeric($id)) {
            $id = (int)$id;

            if (!$id || $id <= 10) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
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
     * Returns a locale object in dependence of the user language
     */
    public function getLocale(): QUI\Locale
    {
        if ($this->Locale) {
            return $this->Locale;
        }

        $this->Locale = new QUI\Locale();
        $this->Locale->setCurrent($this->getLang());

        return $this->Locale;
    }

    /**
     * returns the user language
     */
    public function getLang(): string
    {
        if ($this->lang !== null) {
            return $this->lang;
        }

        if (
            $this->getUUID() === QUI::getUserBySession()->getUUID()
            && QUI::getSession()->get('quiqqer-user-language')
        ) {
            $this->lang = QUI::getSession()->get('quiqqer-user-language');

            return $this->lang;
        }

        $lang = QUI::getLocale()->getCurrent();
        $languages = QUI::availableLanguages();

        if ($this->getAttribute('lang')) {
            $lang = $this->getAttribute('lang');
        }

        if (in_array($lang, $languages)) {
            $this->lang = $lang;
        }

        // if user has no language, use the project language
        if (!$this->lang) {
            try {
                $this->lang = QUI\Projects\Manager::get()->getAttribute('lang');
            } catch (QUI\Exception) {
            }
        }

        if (!$this->lang) {
            $this->lang = QUI::getLocale()->getCurrent();
        }

        return $this->lang;
    }

    /**
     * Returns the user id
     * @deprecated
     */
    public function getId(): int | false
    {
        return $this->id ?: false;
    }

    public function getAttribute(string $name): mixed
    {
        return $this->settings[$name] ?? false;
    }

    /**
     * refresh the data from the database
     *
     * @throws Exception
     * @throws Exception
     * @throws QUI\Exception
     * @throws ExceptionStack
     * @throws \Exception
     */
    public function refresh(): void
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
                    'quiqqer/core',
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
                        QUI\Security\Encryption::decrypt($value)
                    );

                    continue;
                }

                $this->setAttribute($attribute, $value);
            }
        }

        if (isset($data[0]['authenticator'])) {
            $this->authenticator = json_decode($data[0]['authenticator'], true) ?? [];
        }

        // load default address fields
        // syn main user address fields
        $this->isLoaded = true;
        $this->setAttribute('firstname', $data[0]['firstname']);
        $this->setAttribute('lastname', $data[0]['lastname']);
        $this->setAttribute('email', $data[0]['email']);

        $this->address_list = [];
        $this->StandardAddress = null;

        // Event
        QUI::getEvents()->fireEvent('userLoad', [$this]);
    }

    /**
     * Sets a user attribute
     *
     * @throws QUI\Exception
     */
    public function setAttribute(string $key, mixed $value): void
    {
        if (!$key || $key === 'id' || $key === 'password' || $key === 'user_agent') {
            return;
        }

        switch ($key) {
            case "su":
                // only a superuser can set a superuser
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

                if ($this->name !== $value && QUI::getUsers()->usernameExists($value)) {
                    throw new QUI\Users\Exception(
                        QUI::getLocale()->get('quiqqer/core', 'exception.user.name.already.exists')
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
     * is the user su?
     */
    public function isSU(): bool
    {
        return $this->su === true;
    }

    /**
     * Return the standard address from the user
     * If no standard address set, the first address will be returned
     *
     * @return Address|null
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function getStandardAddress(): null | Address
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

            $this->StandardAddress = current($list);

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
     * Get an address from the user
     *
     * @throws \QUI\Users\Exception
     */
    public function getAddress(int | string $id): Address
    {
        if (isset($this->address_list[$id])) {
            return $this->address_list[$id];
        }

        $this->address_list[$id] = new QUI\Users\Address($this, $id);

        return $this->address_list[$id];
    }

    /**
     * Returns all addresses from the user
     *
     * @throws Exception
     */
    public function getAddressList(): array
    {
        $result = QUI::getDataBase()->fetch([
            'from' => Manager::tableAddress(),
            'select' => 'id,uuid',
            'where' => [
                'userUuid' => $this->getUUID()
            ]
        ]);

        if (!isset($result[0])) {
            return [];
        }

        $list = [];

        foreach ($result as $entry) {
            try {
                $list[$entry['uuid']] = $this->getAddress($entry['uuid']);
            } catch (QUI\Users\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $list;
    }

    /**
     * Add an address to the user
     *
     * @throws Exception
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function addAddress(array $params = [], null | QUIUserInterface $ParentUser = null): Address
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
                'userUuid' => $this->getUUID()
            ]
        ]);

        // max 100 addresses per user
        // @todo do it as permission
        if (!empty($addresses[0]['count']) && $addresses[0]['count'] > 100) {
            throw new QUI\Exception([
                'quiqqer/core',
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
                $_params[$needle] = json_encode(
                    Orthos::clearArray($params[$needle])
                );

                continue;
            }

            $_params[$needle] = Orthos::clear($params[$needle]);
        }

        $_params['uid'] = $this->getId();
        $_params['userUuid'] = $this->getUUID();
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
            $this->setAttribute('address', $CreatedAddress->getUUID());
            $this->save($ParentUser);
        }

        return $CreatedAddress;
    }

    /**
     * Checks the edit permissions
     * Can the user be edited by the current user?
     *
     * @param ?QUIUserInterface $ParentUser
     *
     * @return boolean - true
     * @throws QUI\Permissions\Exception
     */
    public function checkEditPermission(null | QUIUserInterface $ParentUser = null): bool
    {
        $Users = QUI::getUsers();
        $SessionUser = $Users->getUserBySession();

        if ($ParentUser && $ParentUser->getType() === SystemUser::class) {
            return true;
        }

        if ($SessionUser->isSU()) {
            return true;
        }

        if ($SessionUser->getId() === $this->getId()) {
            return true;
        }

        if ($SessionUser->getUUID() === $this->getUUID()) {
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
                'quiqqer/core',
                'exception.lib.user.no.edit.rights'
            )
        );
    }

    /**
     * returns the user type
     */
    public function getType(): string
    {
        return $this::class;
    }

    /**
     * Exists the permission in the user permissions
     */
    public function hasPermission(string $permission): bool | string
    {
        $list = QUI::getPermissionManager()->getUserPermissionData($this);

        return $list[$permission] ?? false;
    }

    /**
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     * @throws Exception
     */
    public function save(?QUIUserInterface $PermissionUser = null): void
    {
        $this->checkEditPermission($PermissionUser);

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

            if (strlen($value) == 10) {
                $value .= ' 00:00:00';
            }

            if (Orthos::checkMySqlDatetimeSyntax($value)) {
                $birthday = substr($value, 0, 10);
            }
        }

        $avatar = '';

        if (
            $this->getAttribute('avatar')
            && QUI\Projects\Media\Utils::isMediaUrl($this->getAttribute('avatar'))
        ) {
            $avatar = $this->getAttribute('avatar');
        }

        // on save for module attributes
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
        $this->addToGroup($Everyone->getUUID());

        // check assigned toolbars
        $assignedToolbars = '';
        $toolbar = '';

        if ($this->getAttribute('assigned_toolbar')) {
            $toolbars = explode(',', $this->getAttribute('assigned_toolbar'));

            $assignedToolbars = array_filter($toolbars, static function ($toolbar): bool {
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
        // check if one superuser exists
        if (!$this->isSU()) {
            $superUsers = QUI::getUsers()->getUsers([
                'where' => [
                    'su' => 1,
                    'uuid' => [
                        'type' => 'NOT',
                        'value' => $this->getUUID()
                    ]
                ],
                'limit' => 1
            ]);

            if (!isset($superUsers[0])) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get('quiqqer/core', 'exception.user.save.one.superuser.must.exists')
                );
            }
        }

        // default address filling
        $email = trim($this->getAttribute('email'));
        $this->getStandardAddress();

        if (!$this->getAttribute('address')) {
            $this->setAttribute('address', $this->getStandardAddress()->getUUID());
        }


        // saving
        $query = QUI::getQueryBuilder()->update(Manager::table());

        QUI\Utils\Doctrine::parseDbArrayToQueryBuilder($query, [
            'update' => [
                'username' => $this->getUsername(),
                'usergroup' => ',' . implode(',', $this->getGroups(false)) . ',', // @phpstan-ignore-line
                'firstname' => $this->getAttribute('firstname'),
                'lastname' => $this->getAttribute('lastname'),
                'usertitle' => $this->getAttribute('usertitle'),
                'birthday' => $birthday,
                'email' => $email,
                'avatar' => $avatar,
                'su' => $this->isSU() ? 1 : 0,
                'extra' => json_encode($extra),
                'lang' => $this->getAttribute('lang'),
                'lastedit' => date("Y-m-d H:i:s"),
                'expire' => $expire,
                'shortcuts' => $this->getAttribute('shortcuts'),
                'address' => !empty($this->getAttribute('address')) ? $this->getAttribute('address') : null,
                'company' => $this->isCompany() ? 1 : 0,
                'toolbar' => $toolbar,
                'assigned_toolbar' => $assignedToolbars,
                'authenticator' => json_encode($this->authenticator),
                'lastLoginAttempt' => $this->getAttribute('lastLoginAttempt') ?: null,
                'failedLogins' => $this->getAttribute('failedLogins') ?: 0,
                'verifiableAttributes' => json_encode($this->parseVerifiedAttributesToArray())
            ],
            'where' => [
                'uuid' => $this->getUUID()
            ]
        ]);

        try {
            $query->executeQuery();
        } catch (\Doctrine\DBAL\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage());
        }

        $this->getStandardAddress()->save($PermissionUser);

        QUI::getEvents()->fireEvent('userSaveEnd', [$this]);

        // only for admin users needed
        if ($this->canUseBackend()) {
            QUI\Workspace\Menu::clearMenuCache($this);
        }
    }

    /**
     * Return the list which extra attributes exist
     * Plugins could extend the user attributes
     * look at https://dev.quiqqer.com/quiqqer/core/wikis/User-Xml
     */
    public function getListOfExtraAttributes(): array
    {
        $cache = 'quiqqer/users/user-extra-attributes';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
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
     * Read a user.xml and return the attributes,
     * if some extra attributes defined
     */
    protected function readAttributesFromUserXML(string $file): array
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

        /* @var $Attributes DOMElement */
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

    public function addToGroup(int | string $groupId): void
    {
        try {
            $Groups = QUI::getGroups();
            $Group = $Groups->get($groupId);
        } catch (QUI\Exception) {
            return;
        }

        $groups = $this->getGroups();
        $newGroups = [];
        $_tmp = [];

        $groups[] = $Group;

        foreach ($groups as $UserGroup) {
            /* @var $UserGroup QUI\Groups\Group */
            if (isset($_tmp[$UserGroup->getUUID()])) {
                continue;
            }

            $_tmp[$UserGroup->getUUID()] = true;

            $newGroups[] = $UserGroup->getUUID();
        }

        $this->setGroups($newGroups);
    }

    /**
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     * @return QUI\Groups\Group[]|string[]|array
     */
    public function getGroups(bool $array = true): array
    {
        if ($array === true) {
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

        return [];
    }

    public function setGroups(null | array | string $groups): void
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
                try {
                    $Group = $Groups->get($group);
                } catch (QUI\Exception) {
                    continue;
                }

                $this->Group[] = $Group;
                $aTmp[] = $Group->getUUID();
            }

            $this->groups = ',' . implode(',', $aTmp) . ',';
            return;
        }

        if (str_contains($groups, ',')) {
            $groups = explode(',', $groups);
            $aTmp = [];

            foreach ($groups as $g) {
                if (empty($g)) {
                    continue;
                }

                try {
                    $Group = $Groups->get($g);
                    $this->Group[] = $Group;
                    $aTmp[] = $Group->getUUID();
                } catch (QUI\Exception) {
                    // nothing
                }
            }

            $this->groups = ',' . implode(',', $aTmp) . ',';
            return;
        }


        try {
            $Group = $Groups->get($groups);
            $this->Group[] = $Group;
            $this->groups = ',' . $groups . ',';
        } catch (QUI\Exception) {
        }
    }

    /**
     * @throws QUI\Users\Exception
     */
    protected function checkUserMail(): void
    {
        // check if duplicated emails are exists
        try {
            $email = $this->getAttribute('email');

            if (!empty($email)) {
                $found = QUI::getDataBase()->fetch([
                    'from' => Manager::table(),
                    'where' => [
                        'email' => $email,
                        'uuid' => [
                            'value' => $this->getUUID(),
                            'type' => 'NOT'
                        ]
                    ],
                    'limit' => 1
                ]);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.user.save.mail.exists')
            );
        }

        if (isset($found[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.user.save.mail.exists')
            );
        }
    }

    public function getUsername(): string
    {
        return $this->name ?: false;
    }

    public function isCompany(): bool
    {
        return $this->company;
    }

    public function getAuthenticators(): array
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
     * @param ?QUIUserInterface $ParentUser - optional, the saving user, default = session user
     * @throws QUI\Users\Exception|QUI\Exception
     */
    public function enableAuthenticator(string $authenticator, null | QUIUserInterface $ParentUser = null): void
    {
        $available = Auth\Handler::getInstance()->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator])) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.authenticator.not.found'],
                404
            );
        }

        if (!Auth\Helper::hasUserPermissionToUseAuthenticator($this, $authenticator)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.authenticator.not.found'],
                404
            );
        }

        if (in_array($authenticator, $this->authenticator)) {
            return;
        }

        if (class_exists('QUI\Watcher')) {
            QUI\Watcher::addString(
                QUI::getLocale()->get('quiqqer/core', 'user.enable.authenticator', [
                    'id' => $this->getUUID()
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
     * @param string $authenticator
     * @param QUIUserInterface|null $ParentUser - optional, the saving user, default = session user
     *
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     * @throws Exception
     */
    public function disableAuthenticator(string $authenticator, null | QUIUserInterface $ParentUser = null): void
    {
        $available = Auth\Handler::getInstance()->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator])) {
            throw new QUI\Users\Exception(
                [
                    'quiqqer/core',
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
                QUI::getLocale()->get('quiqqer/core', 'user.disable.authenticator', [
                    'id' => $this->getUUID()
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
    public function hasAuthenticator(string $authenticator): bool
    {
        if (!Auth\Helper::hasUserPermissionToUseAuthenticator($this, $authenticator)) {
            return false;
        }

        return in_array($authenticator, $this->authenticator);
    }

    /**
     * @param string $right
     * @param callable|bool|string $ruleset - optional, you can specify a ruleset, a rules = array with rights
     *
     * @return bool|int|string
     * @throws QUI\Exception
     */
    public function getPermission(string $right, callable | bool | string $ruleset = false): bool | int | string
    {
        //@todo Benutzer muss erster prüfen ob bei ihm das recht seperat gesetzt ist

        return QUI::getPermissionManager()->getUserPermission($this, $right, $ruleset);
    }

    /**
     * @deprecated use getUUID()
     */
    public function getUniqueId(): int | string
    {
        return $this->getUUID();
    }

    public function getUUID(): string | int
    {
        return $this->uuid ?: '';
    }

    public function getStatus(): int
    {
        if ($this->active) {
            return $this->active;
        }

        return 0;
    }

    /**
     * @throws QUI\Exception
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

        if (
            class_exists('QUI\ERP\Currency\Handler')
            && $this->getAttribute('currency')
            && Currencies::existCurrency($this->getAttribute('currency'))
        ) {
            return $this->getAttribute('currency');
        }

        $Country = $this->getCountry();

        if ($Country) {
            $currency = $Country->getCurrencyCode();

            if (class_exists('QUI\ERP\Currency\Handler') && Currencies::existCurrency($currency)) {
                return $currency;
            }
        }

        if (class_exists('QUI\ERP\Currency\Handler')) {
            return Currencies::getDefaultCurrency()->getCode();
        }

        return 'EUR';
    }

    public function getCountry(): ?QUI\Countries\Country
    {
        try {
            $Address = $this->getCurrentAddress();

            if ($Address instanceof Address) {
                return $Address->getCountry();
            }
        } catch (QUI\Exception) {
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

        return null;
    }

    /**
     * Return the current instance address
     * -> Standard Address, Delivery Address or Invoice Address
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function getCurrentAddress(): bool | Address
    {
        $CurrentAddress = $this->getAttribute('CurrentAddress');

        if ($CurrentAddress instanceof Address) {
            return $CurrentAddress;
        }

        return $this->getStandardAddress();
    }

    public function clearGroups(): void
    {
        $this->Group = [];
        $this->groups = '';
    }

    /**
     * @throws QUI\Exception
     */
    public function removeGroup(Group | int | string $Group): void
    {
        $Groups = QUI::getGroups();

        if (is_int($Group) || is_string($Group)) {
            $Group = $Groups->get($Group);
        }

        $groups = $this->getGroups();
        $new_gr = [];

        foreach ($groups as $UserGroup) {
            if ($UserGroup->getUUID() != $Group->getUUID()) {
                $new_gr[] = $UserGroup->getUUID();
            }
        }

        $this->setGroups($new_gr);
    }

    /**
     * @throws QUI\Exception
     * @deprecated use addToGroup
     */
    public function addGroup(int | string $gid): void
    {
        $this->addToGroup($gid);
    }

    public function removeAttribute(string $key): void
    {
        if (!$key || $key === 'id' || $key === 'password' || $key === 'user_agent') {
            return;
        }

        if (isset($this->settings[$key])) {
            unset($this->settings[$key]);
        }
    }

    /**
     * @throws QUI\Exception
     */
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * @deprecated use getAttributes
     */
    public function getAllAttributes(): array
    {
        return self::getAttributes();
    }

    /**
     * Return all user attributes
     */
    public function getAttributes(): array
    {
        $params = $this->settings;
        $params['id'] = $this->getId();
        $params['uuid'] = $this->getUUID();
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

    public function canUseBackend(): bool
    {
        if ($this->admin !== null) {
            return $this->admin;
        }

        $this->admin = QUI\Permissions\Permission::isAdmin($this);

        return $this->admin;
    }

    /**
     * @deprecated
     */
    public function isAdmin(): bool
    {
        return $this->canUseBackend();
    }

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
     * returns the name of the user.
     * - if the user has a first and a lastname
     * - if not, it returns the username
     */
    public function getName(): string
    {
        $firstname = $this->getAttribute('firstname');
        $lastname = $this->getAttribute('lastname');

        if ($firstname && $lastname) {
            return $firstname . ' ' . $lastname;
        }

        return $this->getUsername();
    }

    /**
     * @return ?QUI\Projects\Media\Image
     * @throws ExceptionStack|QUI\Exception
     */
    public function getAvatar(): ?QUI\Projects\Media\Image
    {
        $result = QUI::getEvents()->fireEvent('userGetAvatar', [$this]);

        foreach ($result as $Entry) {
            if ($Entry instanceof QUI\Projects\Media\Image) {
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
     * @throws QUI\Users\Exception
     * @throws QUI\Permissions\Exception|ExceptionStack
     * @throws Exception
     */
    public function changePassword(
        string $newPassword,
        string $oldPassword,
        null | QUIUserInterface $ParentUser = null
    ): void {
        $this->checkEditPermission($ParentUser);

        $newPassword = trim($newPassword);
        $oldPassword = trim($oldPassword);

        if (empty($newPassword) || empty($oldPassword)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.empty.password'
                )
            );
        }

        if (!$this->checkPassword($oldPassword)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
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
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     */
    public function checkPassword(string $pass, bool $encrypted = false): bool
    {
        if ($encrypted) {
            return $pass === $this->password;
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
                'password' => $pass
            ]);

            return true;
        } catch (\Exception $Exception) {
            if (!($Exception instanceof QUI\Users\Exception)) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return false;
    }

    /**
     * Return the authenticators from the user
     * - Which are enabled
     *
     * @param string $authenticator - Name of the authenticator
     * @return AuthenticatorInterface
     *
     * @throws QUI\Users\Exception
     */
    public function getAuthenticator(string $authenticator): AuthenticatorInterface
    {
        $Handler = Auth\Handler::getInstance();
        $available = $Handler->getAvailableAuthenticators();
        $available = array_flip($available);

        if (!isset($available[$authenticator])) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.authenticator.not.found'],
                404
            );
        }

        if (!in_array($authenticator, $this->authenticator)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.authenticator.not.found'],
                404
            );
        }

        if (!Auth\Helper::hasUserPermissionToUseAuthenticator($this, $authenticator)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.authenticator.not.found'],
                404
            );
        }

        return new $authenticator($this->getUsername());
    }

    /**
     * Update password to the database
     *
     * @throws Exception
     */
    protected function updatePassword(string $password): void
    {
        $newPassword = QUI\Security\Password::generateHash($password);
        $this->password = $newPassword;

        QUI::getDataBase()->update(
            Manager::table(),
            ['password' => $newPassword],
            ['uuid' => $this->getUUID()]
        );
    }

    /**
     * @param string $new - new password
     * @param QUIUserInterface|null $PermissionUser
     *
     * @throws ExceptionStack
     * @throws QUI\Permissions\Exception
     * @throws Exception
     * @throws QUI\Users\Exception
     */
    public function setPassword(string $new, null | QUIUserInterface $PermissionUser = null): void
    {
        $this->checkEditPermission($PermissionUser);

        if (empty($new)) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.empty.password'
                )
            );
        }

        QUI::getEvents()->fireEvent('userSetPassword', [$this]);

        $this->updatePassword($new);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.password.save.success'
            )
        );
    }

    /**
     * @param string $code - activation code [optional]
     * @param QUIUserInterface|null $PermissionUser
     * @return bool|int
     *
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Permissions\Exception
     * @throws QUI\Users\Exception
     */
    public function activate(
        string $code = '',
        null | QUIUserInterface $PermissionUser = null
    ): bool | int {
        if (empty($code)) {
            $this->checkEditPermission($PermissionUser);
        }

        QUI::getEvents()->fireEvent('userActivateBegin', [$this, $code, $PermissionUser]);


        // benutzer ist schon aktiv, aktivierung kann nicht durchgeführt werden
        if ($this->isActive()) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.activasion.user.is.activated'
                )
            );
        }

        if ($code && $code != $this->getAttribute('activation')) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
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
                    'quiqqer/core',
                    'exception.lib.user.activation.no.groups'
                )
            );
        }

        if ($this->password === '') {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.activation.no.password'
                )
            );
        }

        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 1],
            ['uuid' => $this->getUUID()]
        );

        $this->active = 1;

        try {
            QUI::getEvents()->fireEvent('userActivate', [$this, $PermissionUser]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), [
                'UserId' => $this->getUUID(),
                'ExceptionType' => $Exception->getType()
            ]);
        }

        return $this->active;
    }

    /**
     * is the user active?
     */
    public function isActive(): bool
    {
        return $this->active === 1;
    }

    /**
     * @param QUIUserInterface|null $PermissionUser (optional) - Executing User
     * @return bool
     *
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     * @throws Exception
     */
    public function deactivate(null | QUIUserInterface $PermissionUser = null): bool
    {
        $this->checkEditPermission($PermissionUser);
        $this->canBeDeleted();

        QUI::getEvents()->fireEvent('userDeactivate', [$this]);

        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 0],
            ['uuid' => $this->getUUID()]
        );

        $this->active = 0;
        $this->logout();

        return true;
    }

    /**
     * Could the user be deleted?
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    protected function canBeDeleted(): bool
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
                QUI::getLocale()->get('quiqqer/core', 'exception.superuser_cannot_delete_himself')
            );
        }

        // Check if user can delete himself
        if (QUI::getUserBySession()->getUUID() === $this->getUUID()) {
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
                    QUI::getLocale()->get('quiqqer/core', 'exception.user.one.superuser.must.exists')
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
                QUI::getLocale()->get('quiqqer/core', 'exception.user.one.active.user.must.exists')
            );
        }

        return true;
    }

    /**
     * @throws QUI\Permissions\Exception
     */
    public function checkPermission($permission): void
    {
        QUI\Permissions\Permission::checkPermission($permission, $this);
    }

    /**
     * logout the use, destroy the session
     * @throws ExceptionStack
     */
    public function logout(): void
    {
        $uuid = $this->getUUID();

        if (empty($uuid)) {
            return;
        }

        QUI::getEvents()->fireEvent('userLogoutBegin', [$this]);

        // Wenn der Benutzer dieser hier ist
        $Users = QUI::getUsers();
        $SessUser = $Users->getUserBySession();

        if ($SessUser->getUUID() === $this->getUUID()) {
            $Session = QUI::getSession();
            $Session->destroy();
        }

        QUI::getEvents()->fireEvent('userLogout', [$this]);
    }

    /**
     * @throws QUI\Exception
     */
    public function disable(null | QUIUserInterface $PermissionUser = null): bool
    {
        $this->checkEditPermission($PermissionUser);
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
            ['uuid' => $this->getUUID()]
        );

        $this->deleted = 1;
        $this->logout();

        QUI\System\Log::write(
            'User disabled.',
            QUI\System\Log::LEVEL_INFO,
            [
                'deletedUserId' => $this->getUUID(),
                'deletedUsername' => $this->getUsername(),
                'executeUserId' => $SessionUser->getUUID(),
                'executeUsername' => $SessionUser->getUsername()
            ],
            'user'
        );

        return true;
    }

    /**
     * @param QUIUserInterface|null $PermissionUser (optional)
     * @return true
     *
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function delete(null | QUIUserInterface $PermissionUser = null): bool
    {
        if (empty($PermissionUser)) {
            $PermissionUser = QUI::getUserBySession();
        }

        $this->checkDeletePermission($PermissionUser);
        $this->deleted = 1;

        // API
        QUI::getEvents()->fireEvent('userDelete', [$this]);

        QUI::getDataBase()->delete(
            Manager::table(),
            ['uuid' => $this->getUUID()]
        );

        // delete all workspaces of this user
        QUI::getDataBase()->delete(
            QUI\Workspace\Manager::table(),
            ['uid' => $this->getId()]
        );

        QUI::getDataBase()->delete(
            QUI\Workspace\Manager::table(),
            ['uid' => $this->getUUID()]
        );

        QUI::getUsers()->onDeleteUser($this);

        $this->logout();

        QUI\System\Log::write(
            'User deleted.',
            QUI\System\Log::LEVEL_INFO,
            [
                'deletedUserId' => $this->getUUID(),
                'deletedUsername' => $this->getUsername(),
                'executeUserId' => $PermissionUser->getUUID(),
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
     * @param QUI\Interfaces\Users\User|null $ParentUser
     *
     * @return boolean - true
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     */
    public function checkDeletePermission(null | QUIUserInterface $ParentUser = null): bool
    {
        $this->canBeDeleted();

        $Users = QUI::getUsers();
        $SessionUser = $Users->getUserBySession();

        if ($ParentUser && $ParentUser->getType() === SystemUser::class) {
            return true;
        }

        if ($SessionUser->isSU()) {
            return true;
        }

        if ($SessionUser->getUUID() === $this->getUUID()) {
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
                'quiqqer/core',
                'exception.lib.user.no.delete.permission'
            )
        );
    }

    public function isInGroup(int | string $groupId): bool
    {
        if (is_numeric($groupId)) {
            QUI\System\Log::addDeprecated('Passing an ID (instead of UUID) to "User::isInGroup" is deprecated.');

            $groupId = (int)$groupId;
            $groups = $this->getGroups(true);

            foreach ($groups as $group) {
                if ($group->getId() === $groupId) {
                    return true;
                }
            }

            return false;
        }

        $groups = $this->getGroups(false);

        return in_array($groupId, $groups);
    }

    /**
     * Set the company status, whether the use is a company or not
     *
     * @param bool $status - true ot false
     */
    public function setCompanyStatus(bool $status = false): void
    {
        $this->company = $status;
    }

    /**
     * is the user deleted?
     */
    public function isDeleted(): bool
    {
        return $this->deleted !== 0;
    }

    /**
     * is the user online?
     */
    public function isOnline(): bool
    {
        return QUI::getSession()->isUserOnline($this->getUUID());
    }

    // region verifiable attributes

    public function setStatusToVerifiableAttribute(
        string $value,
        string $type,
        AttributeVerificationStatus | string $status
    ): void {
        if (!class_exists($type) || !is_subclass_of($type, AbstractVerifiableUserAttribute::class)) {
            return;
        }

        if (is_string($status)) {
            $status = AttributeVerificationStatus::from($status);
        }

        $attributes = $this->getVerifiedAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getValue() === $value) {
                $attribute->setVerificationStatus($status);
                return;
            }
        }

        $attribute = new $type(
            QUI\Utils\Uuid::get(),
            $value,
            $status
        );

        $attributes->add($attribute);
    }

    /**
     * @param float|int|string $value
     * @param string $type - eq: MailAttribute::class; extends AbstractVerifiableUserAttribute, type of the attribute
     * @return bool
     */
    public function isAttributeVerified(
        float | int | string $value,
        string $type
    ): bool {
        if (!class_exists($type) || !is_subclass_of($type, AbstractVerifiableUserAttribute::class)) {
            return false;
        }

        $attributes = $this->getVerifiedAttributes();

        foreach ($attributes as $attribute) {
            if (
                $attribute->getValue() === $value
                && $attribute->getVerificationStatus() === AttributeVerificationStatus::VERIFIED
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     * @return null|AbstractVerifiableUserAttribute
     */
    public function getVerifiedAttribute(string $value): ?AbstractVerifiableUserAttribute
    {
        $attributes = $this->getVerifiedAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getValue() === $value) {
                return $attribute;
            }
        }

        return null;
    }

    public function getVerifiedAttributes(): QUI\Users\Attribute\VerifiableUserAttributeCollection
    {
        if ($this->verifiableAttributes !== null) {
            return $this->verifiableAttributes;
        }

        $verifiableAttributes = new QUI\Users\Attribute\VerifiableUserAttributeCollection();
        $data = json_decode($this->getAttribute('verifiableAttributes'), true) ?? [];

        // mail
        foreach ($data as $verifiableAttribute) {
            if (!isset($verifiableAttribute['type'])) {
                continue;
            }

            if (empty($verifiableAttribute['verification_status'])) {
                continue;
            }


            $class = $verifiableAttribute['type'];

            if (!class_exists($class) || !is_subclass_of($class, AbstractVerifiableUserAttribute::class)) {
                continue;
            }

            $status = AttributeVerificationStatus::tryFrom(
                $verifiableAttribute['verification_status']
            );

            $verifiableAttributes->add(
                new $class(
                    $verifiableAttribute['uuid'],
                    $verifiableAttribute['value'],
                    $status
                )
            );
        }

        $this->verifiableAttributes = $verifiableAttributes;

        return $this->verifiableAttributes;
    }

    protected function parseVerifiedAttributesToArray(): array
    {
        $collection = $this->getVerifiedAttributes();

        if ($collection->isEmpty()) {
            return [];
        }

        $result = [];

        foreach ($collection as $verifiableUserAttribute) {
            $result[] = $verifiableUserAttribute->toArray();
        }

        return $result;
    }
    //endregion
}
