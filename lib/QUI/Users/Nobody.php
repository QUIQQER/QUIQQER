<?php

/**
 * This file contains \QUI\Users\Nobody
 */

namespace QUI\Users;

use QUI;
use QUI\ERP\Currency\Handler as Currencies;

/**
 * The standard user
 * Nobody has no rights
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Nobody extends QUI\QDOM implements QUI\Interfaces\Users\User
{
    /**
     * @var \QUI\Locale|null
     */
    protected ?QUI\Locale $Locale = null;

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
    public function refresh()
    {
        $attributes = QUI::getSession()->get('attributes');

        if (!empty($attributes)) {
            $this->setAttributes($attributes);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::isSU()
     */
    public function isSU(): bool
    {
        return false;
    }

    /**
     * @param int $groupId
     * @return bool
     */
    public function isInGroup($groupId): bool
    {
        return in_array($groupId, $this->getGroups(false));
    }

    /**
     * (non-PHPdoc)
     *
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     *
     * @return array
     * @see \QUI\Interfaces\Users\User::getGroups()
     *
     */
    public function getGroups($array = true): array
    {
        $Guest = new QUI\Groups\Guest();
        $Everyone = new QUI\Groups\Everyone();

        if ($array === true) {
            return [$Guest, $Everyone];
        }

        return [$Guest->getId(), $Everyone->getId()];
    }

    /**
     * (non-PHPdoc)
     *
     * @return bool|int
     * @see \QUI\Interfaces\Users\User::getId()
     *
     */
    public function getId()
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
     * @return false
     */
    public function isCompany(): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::isDeleted()
     */
    public function isDeleted(): bool
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::isActive()
     */
    public function isActive(): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::isOnline()
     */
    public function isOnline(): bool
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::logout()
     */
    public function logout(): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $code - activasion code [optional]
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::activate()
     *
     */
    public function activate($code): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::deactivate()
     *
     */
    public function deactivate(): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @param bool|User $ParentUser
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::disable()
     *
     */
    public function disable($ParentUser = false): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @param bool|User $ParentUser
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::save()
     *
     */
    public function save($ParentUser = false): bool
    {
        QUI::getSession()->set('attributes', $this->getAttributes());

        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::delete()
     *
     */
    public function delete(): bool
    {
        return false;
    }

    /**
     * This method is useless for nobody
     *
     * @param array $params
     *
     * @throws \QUI\Users\Exception
     * @ignore
     */
    public function addAddress(array $params)
    {
        throw new QUI\Users\Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.address'
            )
        );
    }

    /**
     * Return the locale object depending on the user
     *
     * @return \QUI\Locale
     */
    public function getLocale(): ?QUI\Locale
    {
        if ($this->Locale) {
            return $this->Locale;
        }

        $this->Locale = new QUI\Locale();

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
    public function addToGroup($groupId)
    {
        throw new QUI\Users\Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.to.group'
            )
        );
    }

    /**
     * Nobody can't be added to the group
     *
     * @param int $Group
     * @throws Exception
     */
    public function removeGroup($Group)
    {
        throw new QUI\Users\Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.remove.group'
            )
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $field
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::getExtra()
     *
     */
    public function getExtra(string $field): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @return string
     * @see \QUI\Interfaces\Users\User::getType()
     *
     */
    public function getType(): string
    {
        return get_class($this);
    }

    /**
     * (non-PHPdoc)
     *
     * @return false|string
     * @see \QUI\Interfaces\Users\User::getUniqueId()
     *
     */
    public function getUniqueId()
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @return string
     * @see \QUI\Interfaces\Users\User::getName()
     *
     */
    public function getName(): string
    {
        return QUI::getLocale()->get('quiqqer/quiqqer', 'nobody.name');
    }

    /**
     * (non-PHPdoc)
     *
     * @return string
     * @see \QUI\Interfaces\Users\User::getUsername()
     *
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
     * \QUI\Users\Nobody cannot have a address
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
     * \QUI\Users\Nobody cannot have a address
     *
     * @param integer $id
     * @return void
     *
     * @throws \QUI\Users\Exception
     * @ignore
     */
    public function getAddress($id)
    {
        throw new QUI\Users\Exception(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'exception.lib.user.nobody.get.address'
            )
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see iUser::getCurrency()
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
     * @return \QUI\Countries\Country|boolean
     */
    public function getCountry()
    {
        if (QUI::getSession()->get('country')) {
            try {
                return QUI\Countries\Manager::get(
                    QUI::getSession()->get('country')
                );
            } catch (QUI\Exception $Exception) {
            }
        }

        // apache
        if (isset($_SERVER["GEOIP_COUNTRY_CODE"])) {
            try {
                QUI::getSession()->set('country', $_SERVER["GEOIP_COUNTRY_CODE"]);

                return QUI\Countries\Manager::get($_SERVER["GEOIP_COUNTRY_CODE"]);
            } catch (QUI\Exception $Exception) {
            }
        }

        if (QUI::conf('globals', 'country')) {
            try {
                QUI::getSession()->set('country', QUI::conf('globals', 'country'));

                return QUI\Countries\Manager::get(
                    QUI::conf('globals', 'country')
                );
            } catch (QUI\Exception $Exception) {
            }
        }

        // old
        if (QUI::conf('globals', 'defaultCountry')) {
            try {
                QUI::getSession()->set('country', QUI::conf('globals', 'defaultCountry'));

                return QUI\Countries\Manager::get(
                    QUI::conf('globals', 'defaultCountry')
                );
            } catch (QUI\Exception $Exception) {
            }
        }

        return false;
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have a address
     *
     * @return false|Address
     * @ignore
     */
    public function getStandardAddress()
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::getStatus()
     */
    public function getStatus(): bool
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @param string|array $groups
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::setGroups()
     *
     */
    public function setGroups($groups): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @return \QUI\Projects\Media\Image|false
     * @see \QUI\Interfaces\Users\User::getAvatar()
     *
     */
    public function getAvatar()
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
     * (non-PHPdoc)
     *
     * @param string $right
     * @param array|boolean $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     * @return boolean
     *
     * @see \QUI\Interfaces\Users\User::getPermission()
     *
     */
    public function getPermission($right, $ruleset = false): bool
    {
        return QUI::getPermissionManager()->getUserPermission($this, $right, $ruleset);
    }

    /**
     * not usable, nobody is always a company
     *
     * @param bool $status
     */
    public function setCompanyStatus($status = false)
    {
        return;
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $new - new password
     * @param \QUI\Users\User|boolean $ParentUser
     *
     * @return bool
     * @see \QUI\Interfaces\Users\User::setPassword()
     *
     */
    public function setPassword($new, $ParentUser = false): bool
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     *
     * @return false
     * @see \QUI\Interfaces\Users\User::checkPassword()
     *
     */
    public function checkPassword($pass, $encrypted = false): bool
    {
        return false;
    }
}
