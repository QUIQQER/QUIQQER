<?php

/**
 * This file contains Utils_Api_Google_GPlus
 */

/**
 * Easy Access to Google Plus API
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 *
 * @package com.pcsg.qui.utils.api.google
 *
 * @see https://developers.google.com/+/api/latest/people/search
 */

class Utils_Api_Google_GPlus extends QDOM
{
    /**
     * Konstruktor
     *
     * @param Array $params
     * array(
     * 		'key' => ''
     * )
     */
    public function __construct($params)
    {
         $this->setAttributes( $params );
    }

    /**
     * GPlus Initialisieren per API Key
     *
     * @param unknown_type $key
     * @return Utils_Api_Google_GPlus
     */
    static function init($key)
    {
        return new Utils_Api_Google_GPlus(array(
            'key' => $key
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
	    $result = json_decode(
	        Utils_Request_Url::get(
	    		'https://www.googleapis.com/plus/v1/people?query='. $username .
	    		'&pp=1&key='. $this->getAttribute( 'key' )
	        ), true
	    );

	    if ( isset( $result['error'] ) &&
	         isset( $result['error']['errors'] ) &&
	         isset( $result['error']['errors'][0] ) )
	    {
            throw new QException( print_r( $result, true ) );
	    }

	    if ( !isset( $result['items'] ) ) {
            return false;
	    }

	    if ( count( $result['items'] ) ) {
            return true;
	    }

	    return false;
	}

	/**
	 * Return the Activity of an user
	 *
	 * @param String $userid - User ID of the wanted user
	 * @return Array
	 */
	public function getActivity($userid)
	{
        $result = json_decode(
	        Utils_Request_Url::get(
	    		'https://www.googleapis.com/plus/v1/people/'. $userid .
	    		'/activities/public?key='. $this->getAttribute( 'key' )
	        ), true
	    );

	    if ( !isset( $result['items'] ) ) {
            return array();
	    }

	    return $result['items'];
	}
}

?>