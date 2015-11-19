<?php

/**
 * This file contains \QUI\Users\Nobody
 */

namespace QUI\Users;

use QUI;

/**
 * The standard user
 * Nobody has no rights
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package com.pcsg.qui.users
 */
class Nobody extends QUI\QDOM implements QUI\Interfaces\Users\User
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->setAttribute('username', 'nobody');
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
     * @deprecated
     */
    public function isAdmin()
    {
        return $this->canUseBackend();
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
     * @see \QUI\Interfaces\Users\User::activate()
     *
     * @param string $code - activasion code [optional]
     *
     * @return bool
     */
    public function activate($code)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::deactivate()
     *
     * @return bool
     */
    public function deactivate()
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::disable()
     *
     * @param bool|User $ParentUser
     *
     * @return bool
     */
    public function disable($ParentUser = false)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::save()
     *
     * @param bool|User $ParentUser
     *
     * @return bool
     */
    public function save($ParentUser = false)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::delete()
     *
     * @return bool
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
     * @throws \QUI\Exception
     * @ignore
     */
    public function addAddress($params)
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.address'
            )
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getExtra()
     *
     * @param string $field
     *
     * @return bool
     */
    public function getExtra($field)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getType()
     *
     * @return string
     */
    public function getType()
    {
        return get_class($this);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getId()
     *
     * @return false
     */
    public function getId()
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getName()
     *
     * @return string
     */
    public function getName()
    {
        return $this->getUsername();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getUsername()
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getAttribute('username');
    }

    /**
     * Return the user lang
     *
     * @return string
     */
    public function getLang()
    {
        return \QUI::getLocale()->getCurrent();
    }

    /**
     * Return the locale object depending on the user
     *
     * @return \QUI\Locale
     */
    public function getLocale()
    {
        return \QUI::getLocale();
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
        return array();
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have a address
     *
     * @param integer $id
     * @return void
     *
     * @throws \QUI\Exception
     * @ignore
     */
    public function getAddress($id)
    {
        throw new QUI\Exception(
            QUI::getLocale()->get('system', 'exception.lib.user.nobody.get.address')
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
        // apache
        if (isset($_SERVER["GEOIP_COUNTRY_CODE"])) {
            try {
                return QUI\Countries\Manager::get($_SERVER["GEOIP_COUNTRY_CODE"]);

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

            if (QUI\Currency::existCurrency($currency)) {
                return $currency;
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
     * @see \QUI\Interfaces\Users\User::getStatus()
     * @return bool
     */
    public function getStatus()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::setGroups()
     *
     * @param string|array $groups
     *
     * @return bool
     */
    public function setGroups($groups)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getGroups()
     *
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     *
     * @return boolean
     */
    public function getGroups($array = true)
    {
        $Guest    = new QUI\Groups\Guest();
        $Everyone = new QUI\Groups\Everyone();

        if ($array == true) {
            return array($Guest, $Everyone);
        }

        return array($Guest->getId(), $Everyone->getId());
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getAvatar()
     *
     * @param boolean $url - get the avatar with the complete url string
     *
     * @return boolean
     */
    public function getAvatar($url = false)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::getPermission()
     *
     * @param string $right
     * @param array|boolean $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     * @return boolean
     *
     */
    public function getPermission($right, $ruleset = false)
    {
        // @todo "Jeder" Gruppe muss im System vorhanden sein
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::setExtra()
     *
     * @param string $field
     * @param string|integer|array $value
     *
     * @return bool
     */
    public function setExtra($field, $value)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::loadExtra()
     *
     * @param \QUI\Projects\Project $Project
     *
     * @return boolean
     */
    public function loadExtra(QUI\Projects\Project $Project)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::setPassword()
     *
     * @param string $new - new password
     * @param \QUI\Users\User|boolean $ParentUser
     *
     * @return bool
     */
    public function setPassword($new, $ParentUser = false)
    {
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Users\User::checkPassword()
     *
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     *
     * @return false
     */
    public function checkPassword($pass, $encrypted = false)
    {
        return false;
    }
}
