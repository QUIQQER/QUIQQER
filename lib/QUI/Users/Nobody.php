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
     * @var null
     */
    protected $Locale = null;

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
    public function isSU()
    {
        return false;
    }

    /**
     * @param int $groupId
     * @return mixed
     */
    public function isInGroup($groupId)
    {
        return in_array($groupId, $this->getGroups(false));
    }

    /**
     * @deprecated
     */
    public function isAdmin()
    {
        return $this->canUseBackend();
    }

    /**
     * Nobody is no company
     * @return false
     */
    public function isCompany()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canUseBackend()
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::isDeleted()
     */
    public function isDeleted()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::isActive()
     */
    public function isActive()
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::isOnline()
     */
    public function isOnline()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::logout()
     */
    public function logout()
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
    public function activate($code)
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
    public function deactivate()
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
    public function disable($ParentUser = false)
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
    public function save($ParentUser = false)
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
    public function delete()
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
    public function addAddress($params)
    {
        throw new QUI\Users\Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.address'
            )
        );
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
    public function getExtra($field)
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
    public function getType()
    {
        return get_class($this);
    }

    /**
     * (non-PHPdoc)
     *
     * @return false
     * @see \QUI\Interfaces\Users\User::getId()
     *
     */
    public function getId()
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @return false
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
    public function getName()
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
    public function getUsername()
    {
        return QUI::getLocale()->get('quiqqer/quiqqer', 'nobody.username');
    }

    /**
     * Return the user lang
     *
     * @return string
     */
    public function getLang()
    {
        return self::getLocale()->getCurrent();
    }

    /**
     * Return the locale object depending on the user
     *
     * @return \QUI\Locale
     */
    public function getLocale()
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
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have a address
     *
     * @return array
     * @ignore
     */
    public function getAddressList()
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
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have a address
     *
     * @return false
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
    public function getStatus()
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
    public function setGroups($groups)
    {
        return false;
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
    public function getGroups($array = true)
    {
        $Guest    = new QUI\Groups\Guest();
        $Everyone = new QUI\Groups\Everyone();

        if ($array == true) {
            return [$Guest, $Everyone];
        }

        return [$Guest->getId(), $Everyone->getId()];
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
        $Media   = $Project->getMedia();

        return $Media->getPlaceholderImage();
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

        return isset($list[$permission]) ? $list[$permission] : false;
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
    public function getPermission($right, $ruleset = false)
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
    public function setPassword($new, $ParentUser = false)
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
    public function checkPassword($pass, $encrypted = false)
    {
        return false;
    }
}
