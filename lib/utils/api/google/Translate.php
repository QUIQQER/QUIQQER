<?php

/**
* This file contains Utils_Api_Google_Translation
* @package com.pcsg.qui.utils.api.google
*/

/**
 * Google Translation API
 *
 * Easy use of the Google Translation API v1 and 2
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.api.google
 *
 * @example $Translate = new Utils_Api_Google_Translation(array(
 * 		'key' => 'my_google_api_key'
 * ));
 *
 * $Translate->translate_v2('de', 'en', 'Mein Text zum übersetzen');
 */

class Utils_Api_Google_Translation extends \QUI\QDOM
{
    /**
     * Constructor
     *
     * @param unknown_type $attr
     *	key = Google API Key, optional
     */
    public function __construct($attr)
    {
        $this->setAttributes($attr);
    }

    /**
     * Translation API v2
     *
     * @param String $from
     * @param String $to
     * @param String $text
     *
     * @return String
     */
    public function translate_v2($from, $to, $text)
    {
        $text = mb_strtolower( $text );

        $url  = 'https://www.googleapis.com/language/translate/v2?';
        $url .= 'key='. $this->getAttribute('key');
        $url .= '&q='. $text;
        $url .= '&source='. $from;
        $url .= '&target='. $to;

        $Curl = curl_init();

        curl_setopt( $Curl, CURLOPT_URL, $url );
        curl_setopt( $Curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $Curl, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt( $Curl, CURLOPT_TIMEOUT, 10 );

        $data = curl_exec( $Curl );
        $data = json_decode( $data, true );

        if ( isset($data['error']) || !isset($data['data']) )
        {
            if ( isset($data['error']) ) {
                System_Log::writeRecursive( $message, 'error' );
            }

            throw new \QUI\Exception('Es konnte keine Übersetzung gefunden werden');
        }

        if ( $data['data']['translations'][0]['translatedText'] == $text ) {
            throw new \QUI\Exception('Es konnte keine Übersetzung gefunden werden');
        }

        return $data['data']['translations'][0]['translatedText'];
    }

    /**
     * Translation API v1
     *
     * @param String $from
     * @param array $to
     * @param String $text
     * @return Array
     */
    public function translate($from, array $to, $text)
    {
        $text      = urlencode( $text );
        $langpairs = '';

        foreach ( $to as $lang ) {
            $langpairs .= '&langpair='. $from .'|'. $lang;
        }

        $url  = 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q='. $text;
        $url .= $langpairs;
        $url .= '&userip='. \QUI\Utils\System::getClientIP();

        if ( $this->getAttribute( 'key' ) ) {
            $url .= '&key='. $this->getAttribute( 'key' );
        }

        $Curl = curl_init();

        curl_setopt( $Curl, CURLOPT_URL, $url );
        curl_setopt( $Curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $Curl, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt( $Curl, CURLOPT_TIMEOUT, 10 );

        $exec = curl_exec( $Curl );
        $exec = json_decode( $exec, true );

        $result = array();

        for ( $i = 0, $len = count($exec['responseData']); $i < $len; $i++ )
        {
            // Falls nur ein Ergebniss gibt - danke google :-/
            if ( isset($exec['responseData']['translatedText']) )
            {
                $result[ $to[$i] ] = $exec['responseData']['translatedText'];
                continue;
            }

            if ( !isset($exec['responseData'][$i]) ) {
                continue;
            }

            if ( !isset($exec['responseData'][$i]["responseData"]) ) {
                continue;
            }

            if ( !isset($exec['responseData'][$i]["responseData"]["translatedText"]) ) {
                continue;
            }

            $result[ $to[$i] ] = $exec['responseData'][$i]["responseData"]["translatedText"];
        }

        return $result;
    }
}

?>