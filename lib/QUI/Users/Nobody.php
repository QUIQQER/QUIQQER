<?php

/**
 * This file contains \QUI\Users\Nobody
 */

namespace QUI\Users;

/**
 * The standard user
 * Nobody has no rights
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */
class Nobody extends \QUI\QDOM implements \QUI\Interfaces\Users\User
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
     * @see \QUI\Interfaces\Users\User::isSU()
     */
    public function isSU() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::isAdmin()
     */
    public function isAdmin() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::isDeleted()
     */
    public function isDeleted() {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::isActive()
     */
    public function isActive() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::isOnline()
     */
    public function isOnline() {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::logout()
     */
    public function logout() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::activate()
     *
     * @param String $code - activasion code [optional]
     */
    public function activate($code) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::deactivate()
     */
    public function deactivate() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::disable()
     */
    public function disable() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::save()
     */
    public function save() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::delete()
     */
    public function delete() {
        return false;
    }

    /**
     * This method is useless for nobody
     *
     * @param array $params
     * @throws \QUI\Exception
     * @ignore
     */
    public function addAddress($params)
    {
        throw new \QUI\Exception(
            \QUI::getLocale(
                'system',
                'exception.lib.user.nobody.add.address'
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getExtra()
     *
     * @param String $field
     * @return false
     */
    public function getExtra($field) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getType()
     *
     * @return String
     */
    public function getType() {
        return get_class($this);
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getId()
     *
     * @return false
     */
    public function getId() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getName()
     *
     * @return String
     */
    public function getName() {
        return $this->getAttribute('username');
    }

    /**
     * Return the user lang
     * @return String
     */
    public function getLang() {
        return \QUI::getLocale()->getCurrent();
    }

    /**
     * Return the locale object depending on the user
     * @return \QUI\Locale
     */
    public function getLocale() {
        return \QUI::getLocale();
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have a address
     *
     * @return array
     * @ignore
     */
    public function getAddressList() {
        return array();
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have a address
     *
     * @param Integer $id
     * @throws \QUI\Exception
     * @ignore
     */
    public function getAddress($id)
    {
        throw new \QUI\Exception(
            \QUI::getLocale('system', 'exception.lib.user.nobody.get.address')
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
        if ( isset( $_SERVER[ "GEOIP_COUNTRY_CODE" ] ) )
        {
            try
            {
                return \QUI\Countries\Manager::get( $_SERVER[ "GEOIP_COUNTRY_CODE" ] );

            } catch ( \QUI\Exception $Exception )
            {

            }
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see iUser::getCurrency()
     */
    public function getCurrency()
    {
        if ( \QUI::getSession()->get( 'currency' ) )
        {
            $currency = \QUI::getSession()->get( 'currency' );

            if ( \QUI\Currency::existCurrency( $currency ) ) {
                return $currency;
            }
        }

        $Country = $this->getCountry();

        if ( $Country )
        {
            $currency = $Country->getCurrencyCode();

            if ( \QUI\Currency::existCurrency( $currency ) ) {
                return $currency;
            }
        }

        return \QUI\Currency::getDefaultCurrency();
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have a address
     *
     * @return false
     * @ignore
     */
    public function getStandardAddress() {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getStatus()
     */
    public function getStatus() {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::setGroups()
     *
     * @param String|array
     */
    public function setGroups($groups) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getGroups()
     *
     * @param Bool $array - returns the groups as objects (true) or as an array (false)
     */
    public function getGroups($array=true) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getAvatar()
     * @param Bool $url - get the avatar with the complete url string
     */
    public function getAvatar($url=false) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getPermission()
     *
     * @param String $right
     * @param array $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     */
    public function getPermission($right, $ruleset=false) {
        // @todo "Jeder" Gruppe muss im System vorhanden sein
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::setExtra()
     *
     * @param String $field
     * @param String|Integer|array $value
     */
    public function setExtra($field, $value) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::loadExtra()
     *
     * @param \QUI\Projects\Project $Project
     */
    public function loadExtra(\QUI\Projects\Project $Project) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::setPassword()
     *
     * @param String $new - new password
     */
    public function setPassword($new) {
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::checkPassword()
     *
     * @param String $pass    - Password
     * @param Bool $encrypted - is the given password already encrypted?
     *
     * @return false
     */
    public function checkPassword($pass, $encrypted=false) {
        return false;
    }
}
