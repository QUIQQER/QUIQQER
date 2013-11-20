<?php

/**
 * This file contains Utils_Request_Url
 */

/**
 * Executes a request to a URL
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.request
 */

class Utils_Request_Url
{
    /**
     * List of curl Objects
     * @var Array
     */
    static $Curls = array();

    /**
     * Get the Curl Objekt
     *
     * @param String $url		- Url
     * @param array $curlparams - Curl parameter
     * @see http://www.php.net/manual/de/function.curl-setopt.php
     */
    static function Curl($url, $curlparams=array())
    {
        if (isset(self::$Curls[$url]) && self::$Curls[$url]) {
            return self::$Curls[$url];
        }

        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_URL, $url);
		curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);

		// ssl
		curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($Curl, CURLOPT_SSL_VERIFYHOST, 0);

		curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

		foreach ($curlparams as $k => $v) {
            curl_setopt($Curl, $k, $v);
		}

		self::$Curls[$url] = $Curl;

		return $Curl;
    }

    /**
     * Get the content from a url
     *
     * @param String $url
     * @param array $curlparams - see Utils_Request_Url::Curl (optional)
     */
    static function get($url, $curlparams=array())
    {
        $Curl = self::Curl($url, $curlparams);
		$data = self::exec($Curl);

		$http_code = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
		$error     = curl_error($Curl);

		if ($error) {
            throw new \QUI\Exception('Fehler bei der Anfrage '. $error);
		}

		curl_close($Curl);

		return $data;
    }

    /**
     * Search the string at the content of the url
     *
     * @param String $url
     * @param String $search
     * @param array $curlparams - siehe Utils_Request_Url::Curl (optional)
     *
     * @return Bool
     */
    static function search($url, $search, $curlparams=array())
    {
        $content = self::get($url, $curlparams);

        return strpos($content, $search) === false ? false : true;
    }

    /**
     * Get a header information of the url
     *
     * @param String $url
     * @param CURL OPT $info
     * @param array $curlparams - see Utils_Request_Url::Curl (optional)
     */
    static function getInfo($url, $info=false, $curlparams=array())
    {
        $Curl = self::Curl($url, $curlparams);
		$data = self::exec($Curl);

		if ($info)
		{
		    $result = curl_getinfo($Curl, $info);
		} else
		{
            $result = curl_getinfo($Curl);
		}

		$error = curl_error($Curl);

		if ($error) {
            throw new \QUI\Exception('Fehler bei der Anfrage '. $error);
		}

		curl_close($Curl);

        return $result;
    }

    /**
     * exec the curl object
     *
     * @param curl_init $Curl
     * @return curl_init
     */
    static function exec($Curl)
    {
		if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off')
		{
            curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, false);

            $newurl = curl_getinfo($Curl, CURLINFO_EFFECTIVE_URL);
            $rch    = curl_copy_handle($Curl);

            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
            do {
                curl_setopt($rch, CURLOPT_URL, $newurl);
                $header = curl_exec($rch);
                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $newurl = trim(array_pop($matches));
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$mr);

            curl_close($rch);
            curl_setopt($Curl, CURLOPT_URL, $newurl);
		}

        return curl_exec($Curl);
    }
}

?>