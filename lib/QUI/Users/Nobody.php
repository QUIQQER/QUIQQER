<?php

/**
 * This file contains \QUI\Users\Nobody
 */

namespace QUI\Users;

use QUI;
use QUI\Countries\Country;
use QUI\ERP\Currency\Handler as Currencies;
use QUI\Exception;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Projects\Media\Image;

/**
 * The standard user
 * Nobody has no rights
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Nobody extends QUI\QDOM implements User
{
    /**
     * @var Locale|null
     */
    protected ?Locale $Locale = null;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->refresh();
    }

    /**
     * refresh the nobody object
     * reads the data from the session
     */
    public function refresh(): void
    {
        $attributes = QUI::getSession()->get('attributes');

        if (!empty($attributes)) {
            $this->setAttributes($attributes);
        }
    }

    /**
     * @return bool
     */
    public function isSU(): bool
    {
        return false;
    }

    /**
     * @param int|string $groupId
     * @return bool
     */
    public function isInGroup(int|string $groupId): bool
    {
        return in_array($groupId, $this->getGroups(false));
    }

    /**
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     * @return array
     */
    public function getGroups(bool $array = true): array
    {
        $Guest = new QUI\Groups\Guest();
        $Everyone = new QUI\Groups\Everyone();

        if ($array === true) {
            return [$Guest, $Everyone];
        }

        return [$Guest->getId(), $Everyone->getId()];
    }

    /**
     * @return bool|int
     */
    public function getId(): false|int
    {
        return false;
    }

    /**
     * @deprecated
     */
    public function isAdmin(): bool
    {
        return $this->canUseBackend();
    }

    /**
     * @return bool
     */
    public function canUseBackend(): bool
    {
        return false;
    }

    /**
     * Nobody is no company
     *
     * @return false
     */
    public function isCompany(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isOnline(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function logout(): bool
    {
        return false;
    }

    /**
     * @param string $code - activation code [optional]
     * @param User|null $PermissionUser
     * @return bool
     */
    public function activate(string $code, User $PermissionUser = null): bool
    {
        return false;
    }

    /**
     * @param User|null $PermissionUser
     * @return bool
     */
    public function deactivate(?User $PermissionUser = null): bool
    {
        return false;
    }

    /**
     * @param User|null $PermissionUser
     * @return bool
     */
    public function disable(?User $PermissionUser = null): bool
    {
        return false;
    }

    /**
     * @param User|null $PermissionUser
     * @return bool
     */
    public function save(?User $PermissionUser = null): bool
    {
        QUI::getSession()->set('attributes', $this->getAttributes());

        return true;
    }

    /**
     * @param User|null $PermissionUser
     * @return bool
     */
    public function delete(?User $PermissionUser = null): bool
    {
        return false;
    }

    /**
     * This method is useless for nobody
     *
     * @param array $params
     *
     * @throws Exception
     * @ignore
     */
    public function addAddress(array $params)
    {
        throw new Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.address'
            )
        );
    }

    /**
     * Return the locale object depending on the user
     *
     * @return Locale
     */
    public function getLocale(): Locale
    {
        if ($this->Locale) {
            return $this->Locale;
        }

        $this->Locale = new Locale();

        if (QUI::getSession()->get('CURRENT_LANG')) {
            $this->Locale->setCurrent(QUI::getSession()->get('CURRENT_LANG'));
        } else {
            $this->Locale->setCurrent(QUI::getLocale()->getCurrent());
        }

        return $this->Locale;
    }

    /**
     * Nobody can't be added to the group
     *
     * @param int $groupId
     * @throws Exception
     */
    public function addToGroup(int $groupId)
    {
        throw new Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.to.group'
            )
        );
    }

    /**
     * Nobody can't be added to the group
     *
     * @param int|Group $Group
     * @throws Exception
     */
    public function removeGroup(Group|int $Group)
    {
        throw new Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.remove.group'
            )
        );
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public function getExtra(string $field): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this::class;
    }

    /**
     * @return int|string
     */
    public function getUniqueId(): int|string
    {
        return $this->getUUID();
    }

    /**
     * @return string|int
     */
    public function getUUID(): string|int
    {
        return '';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return QUI::getLocale()->get('quiqqer/quiqqer', 'nobody.name');
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return QUI::getLocale()->get('quiqqer/quiqqer', 'nobody.username');
    }

    /**
     * Return the user lang
     *
     * @return string
     */
    public function getLang(): string
    {
        return self::getLocale()->getCurrent();
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have an address
     *
     * @return array
     * @ignore
     */
    public function getAddressList(): array
    {
        return [];
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have an address
     *
     * @param integer|string $id
     * @return Address
     *
     * @throws Exception
     * @ignore
     */
    public function getAddress(int|string $id): Address
    {
        throw new Exception(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'exception.lib.user.nobody.get.address'
            )
        );
    }

    /**
     */
    public function getCurrency()
    {
        if (QUI::getSession()->get('currency')) {
            $currency = QUI::getSession()->get('currency');

            if (Currencies::existCurrency($currency)) {
                return $currency;
            }
        }

        $Country = $this->getCountry();

        if ($Country) {
            $currency = $Country->getCurrencyCode();

            if (Currencies::existCurrency($currency)) {
                return $currency;
            }
        }

        return Currencies::getDefaultCurrency();
    }

    /**
     * Return the Country of nobody
     * use the GEOIP_COUNTRY_CODE from apache, if available
     *
     * @return Country|boolean
     */
    public function getCountry(): Country|bool
    {
        if (QUI::getSession()->get('country')) {
            try {
                return QUI\Countries\Manager::get(
                    QUI::getSession()->get('country')
                );
            } catch (QUI\Exception) {
            }
        }

        // apache
        if (isset($_SERVER["GEOIP_COUNTRY_CODE"])) {
            try {
                QUI::getSession()->set('country', $_SERVER["GEOIP_COUNTRY_CODE"]);

                return QUI\Countries\Manager::get($_SERVER["GEOIP_COUNTRY_CODE"]);
            } catch (QUI\Exception) {
                QUI::getSession()->del('country');
            }
        }

        if (QUI::conf('globals', 'country')) {
            try {
                QUI::getSession()->set('country', QUI::conf('globals', 'country'));

                return QUI\Countries\Manager::get(QUI::conf('globals', 'country'));
            } catch (QUI\Exception) {
                QUI::getSession()->del('country');
            }
        }

        // old
        if (QUI::conf('globals', 'defaultCountry')) {
            try {
                QUI::getSession()->set('country', QUI::conf('globals', 'defaultCountry'));

                return QUI\Countries\Manager::get(
                    QUI::conf('globals', 'defaultCountry')
                );
            } catch (QUI\Exception) {
            }
        }

        return false;
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have an address
     *
     * @return false|Address
     * @ignore
     */
    public function getStandardAddress()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return true;
    }

    /**
     * @param array|string $groups
     *
     * @return bool
     */
    public function setGroups(array|string $groups): bool
    {
        return false;
    }

    /**
     * @return Image|false
     * @throws Exception
     */
    public function getAvatar(): Image|bool
    {
        $Project = QUI::getProjectManager()->getStandard();
        $Media = $Project->getMedia();

        return $Media->getPlaceholderImage();
    }

    /**
     * Exists the permission in the user permissions
     *
     * @param string $permission
     *
     * @return boolean|string
     */
    public function hasPermission(string $permission)
    {
        $list = QUI::getPermissionManager()->getUserPermissionData($this);

        return $list[$permission] ?? false;
    }

    /**
     * @param string $right
     * @param boolean|array $ruleset - optional, you can specify a ruleset, a rules = array with rights
     * @return bool|int|string
     *
     * @throws Exception
     */
    public function getPermission(string $right, bool|array $ruleset = false): bool|int|string
    {
        return QUI::getPermissionManager()->getUserPermission($this, $right, $ruleset);
    }

    /**
     * not usable, nobody is always a company
     *
     * @param bool $status
     */
    public function setCompanyStatus(bool $status = false): void
    {
    }

    /**
     * @param string $new - new password
     * @param \QUI\Users\User|boolean $PermissionUser
     *
     * @return bool
     */
    public function setPassword(string $new, $PermissionUser = false): bool
    {
        return false;
    }

    /**
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     *
     * @return false
     */
    public function checkPassword(string $pass, bool $encrypted = false): bool
    {
        return false;
    }
}
