<?php

/**
 * This file contains Utils_Api_Facebook
 */

/**
 * Easy access to FaceBook
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg-de (Moritz Scholz)
 * @package com.pcsg.qui.utils.api
 */

class Utils_Api_Facebook extends QDOM
{
	/**
     * Opengraph Suche
     *
     * All public posts: https://graph.facebook.com/search?q=watermelon&type=post
     * People: https://graph.facebook.com/search?q=mark&type=user
     * Pages: https://graph.facebook.com/search?q=platform&type=page
     * Events: https://graph.facebook.com/search?q=conference&type=event
     * Groups: https://graph.facebook.com/search?q=programming&type=group
     * Places: https://graph.facebook.com/search?q=coffee&type=place&center=37.76,122.427&distance=1000
     * Checkins: https://graph.facebook.com/search?type=checkin
     *
     * @param array $params
     * @example Utils_Api_Facebook::search(array(
     * 		'q'    => 'platform',
     * 		'type' => 'page'
     * ));
     * @return Bool
     * @todo oAuth
     */
	static function search($params)
	{
	    $result = json_decode(
	        Utils_Request_Url::get("https://graph.facebook.com/search?". http_build_query($params)),
	        true
	    );

	    return $result;
	}

	/**
     * Prüft ob der Nutzername bereits existiert
     *
     * @param String $username
     * @return Bool
     */
	static function userExist($username)
	{
	    $data = json_decode(
	        Utils_Request_Url::get(
	        	'https://graph.facebook.com/'. $username
	        ), true
	    );

	    if (isset($data['error']) &&
	        !empty($data['error']))
	    {
            return 0;
	    }

	    return 1;
	}
}

?>