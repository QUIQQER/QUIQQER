<?php

/**
 * This file contains Utils_Api_Twitter
 */

/**
 * Easy access to Twitter
 * Twitter Klasse um Nachrichten auf Twitter zu veröffentlichen und kontakt aufzunehmen
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 * @package com.pcsg.qui.utils.api
 *
 * @example
 * $Twitter = new Utils_Api_Twitter();
 * $Twitter->setAttribute('username', 'USERNAME');
 * $Twitter->setAttribute('password', 'PASSWORD');
 *
 * $Twitter->send('My New Message');
 */

class Utils_Api_Twitter extends \QUI\QDOM
{
    /**
     * cachefolder for twitter cache
     * @var Bool|String
     */
    protected $_cachefolder = false;

    /**
     * lifetime of the cache
     * @var Integer
     */
    protected $_cachetime = 3600;

    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct($params=array())
    {
        if ( !is_array( $params ) ) {
            return;
        }

        foreach ( $params as $key => $value ) {
            $this->setAttribute( $key, $value );
        }


        if ( $this->getAttribute( 'cachefolder' ) )
        {
            $this->_cachefolder = $this->getAttribute( 'cachefolder' );
            \QUI\Utils\System\File::mkdir( $this->_cachefolder );
        }

        if ( $this->getAttribute( 'cachetime' ) ) {
            $this->_cachetime = $this->getAttribute( 'cachetime' );
        }
    }

    /**
     * Attribute setzen
     *
     * @param String $name
     * @param unknown_type $value
     */
    public function setAttribute($name, $value)
    {
        parent::setAttribute( $name, $value );

        if ( $name == 'cachefolder' )
        {
            $this->_cachefolder = $value;
            \QUI\Utils\System\File::mkdir( $this->_cachefolder );
        }

        if ( $name == 'cachetime' ) {
            $this->_cachetime = $value;
        }
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
                "http://api.twitter.com/1/users/show.json?screen_name=". $username
            ), true
        );

        if ( isset( $data['id'] ) ) {
            return 1;
        }

        if ( isset( $data['error'] ) &&
             !empty( $data['error'] ) )
        {
            if ( strpos( $data['error'], 'suspended' ) ) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    /**
     * Send a Message to Twitter
     *
     * @param String $message
     * @return Bool
     */
    public function send($message)
    {
        $message = str_replace( '&', '%26', $message );

        if ( strlen( $message ) > 140 ) {
            throw new \QUI\Exception( 'Deine Nachricht ist länger als 140 Zeichen' );
        }

        if ( strlen( $message ) <= 0 ) {
            throw new \QUI\Exception( 'Du hast keine Nachricht angegeben' );
        }

        $str = 'status='. urlencode( $message );

        if ( $this->getAttribute( 'source' ) ) {
            $str .= '&source='. $this->getAttribute( 'source' );
        }

        return $this->_exec(
            'http://twitter.com/statuses/update.xml',
            'status='. $message
        );
    }

    /**
     * Returns the List of the Followers
     *
     * @return unknown
     */
    public function getFollowers()
    {
        return json_decode(
            $this->_exec( 'http://twitter.com/statuses/followers.json' ),
            true
        );
    }

    /**
     * Returns the List of the Featured Users
     *
     * @return unknown
     */
    public function getFeatured()
    {
        return json_decode(
            $this->_exec( 'http://twitter.com/statuses/featured.json' ),
            true
        );
    }

    /**
     * Returns the Users Public Tweets
     *
     * @return Array
     */
    public function getTweet()
    {
        //$request = 'http://twitter.com/statuses/user_timeline/'. $this->getAttribute('username') .'.json?';
        $request = 'http://api.twitter.com/1/statuses/user_timeline/'.
                   $this->getAttribute('username') .'.json?';

        if ( $this->getAttribute( 'count' ) ) {
            $request .= 'count='. $this->getAttribute( 'count' );
        }

        if ( $this->getAttribute( 'include_rts' ) ) {
            $request .= '&include_rts=1';
        }

        $cf = false; // Cachefile

        if ( $this->_cachefolder )
        {
            $cf = 'tweet_'. $this->getAttribute( 'username' );

            if ( $this->getAttribute( 'count' ) ) {
                $cf .= '_count_'. $this->getAttribute( 'count' );
            }

            $cf = $this->_cachefolder . $cf;

            /**
             * Cache zurück geben
             */
            if ( file_exists( $cf ) )
            {
                $result = json_decode( file_get_contents( $cf ), true );

                if ( isset($result['result']) &&
                     isset($result['time']) &&
                     $result['time'] < time() + $this->_cachetime )
                {
                    return $result['result'];
                }
            }
        }

        $result = json_decode( $this->_exec( $request ), true );

        if ( $cf == false ) {
            return $result;
        }

        if ( file_exists( $cf ) ) {
            unlink( $cf );
        }

        file_put_contents($cf, json_encode(array(
            'result' => $result,
            'time'   => time()
        )));

        return $result;
    }

    /**
     * Returns the Users Tweets
     *
     * @return Array
     */
    public function getProfile()
    {
        return json_decode(
            $this->_exec(
                'http://twitter.com/users/show/'. $this->getAttribute('username') .'.json'
            ),
            true
        );
    }

    /**
     * Returns a big URL as small URL from tinyurl.com
     *
     * @param  string $url
     * @return string $TynyURL
     */
    public function getTinyUrl($url)
    {
        return $this->_exec( 'http://tinyurl.com/api-create.php?url='. $url );
    }

    /**
     * Wandelt einen Tweet in geeignetes HTML um
     * Links Umwandlung
     *
     * @param String $tweet
     * @return String
     */
    static function parseToHtml($tweet)
    {
        $tweet = $tweet .' ';

        $tweet = preg_replace_callback(
            '/(http:\/\/.*?[^ ] )(.*?)$/',
            array('Utils_Api_Twitter', 'parseToHtmlCallback'),
            $tweet
        );

        $tweet = preg_replace_callback(
            '/(\#.*?[^ ] )(.*?)/is',
            array('Utils_Api_Twitter', 'parseToHtmlCallbackAnker'),
            $tweet
        );

        return $tweet;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $params
     * @return unknown
     */
    static function parseToHtmlCallback($params)
    {
        $params[1] = str_replace( ' ', '', $params[1] );
        return '<a href="'. $params[1] .'">'. $params[1] .'</a> '. $params[2];
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $params
     * @return unknown
     */
    static function parseToHtmlCallbackAnker($params)
    {
        if ( mb_strpos( $params[0], '#' ) != 0 ) {
            return $params[0];
        }

        if ( mb_substr($params[0], 1, 1 ) == ' ' ) {
            return $params[0];
        }

        $params[1] = str_replace( ' ', '', $params[1] );

        return '<a href="http://twitter.com/#!/search/'. urlencode( $params[1] ) .'" target="_blank">'.
            $params[1] .
        '</a> '. $params[2];
    }

    /**
     * Twitter Excute with Curl
     *
     * @param String $url
     * @param String $post
     * @return Bool || Twitter return
     */
    protected function _exec($url, $post=false)
    {
        $curl = curl_init();

        if ( $this->getAttribute( 'username' ) &&
             $this->getAttribute( 'password' ) )
        {
            curl_setopt(
                $curl,
                CURLOPT_USERPWD,
                $this->getAttribute('username') .':'. $this->getAttribute('password')
            );
        }

        if ( $post )
        {
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $post );
            curl_setopt( $curl, CURLOPT_POST, 1 );
        }

        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );


        $return = curl_exec( $curl );
        curl_close( $curl );

        if ( empty( $return ) ) {
            return false;
        }

        return $return;
    }
}

?>