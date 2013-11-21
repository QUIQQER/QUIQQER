<?php

/**
 * This file contains Utils_Api_Flickr
 */

/**
 * Easy Access to Flickr API
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.api
 * @uses phpFlickr Class by Dan Coulter
 */

class Utils_Api_Flickr extends \QUI\QDOM
{
    /**
     * Konstruktor
     *
     * @param Array $params
     * array(
     * 		'apikey' => '',
     * 		'secret' => ''
     * )
     */
    public function __construct($params)
    {
         $this->setAttributes($params);
    }

    /**
     * GPlus Initialisieren per API Key
     *
     * @param String $apikey
     * @param String $secret
     * @return Utils_Api_Flickr
     */
    static function init($apikey='', $secret='')
    {
        return new Utils_Api_Flickr(array(
            'apikey' => $apikey,
            'secret' => $secret
        ));
    }

    /**
     * Prüft ob der Nutzername bereits existiert
     *
     * @param String $username
     * @return Bool
     */
    public function userExist($username)
    {
        require_once dirname(__FILE__) .'/flickr/phpFlickr.php';

        $Flickr = new phpFlickr(
            $this->getAttribute('apikey'),
            $this->getAttribute('secret')
        );

        $result = $Flickr->people_findByUsername($username);

        if (!$result) {
            return false;
        }

        if (isset($result['id'])) {
            return true;
        }

        return false;
    }
}

?>