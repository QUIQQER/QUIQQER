<?php

/**
 * This file contains Utils_Request_Linkchecker
 */

/**
 * QUIQQER Linkchecker
 *
 * Methods to check urls
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.request
 * @copyright PCSG
 *
 * @todo Extension for link detection -> Special checks, for example etracker
 * @todo Linkcheck over SSL
 * @todo rewrite it and use Utils_Request_Url
 *
 * @uses Curl
 */

class Utils_Request_Linkchecker extends \QUI\QDOM
{
    const USER_AGENT = 'PCSG LinkChecker (http://www.pcsg.eu/)';

    /**
     * Crawlt alle Links einer URL
     *
     * @param String $url - Zum Beispiel: http://www.pcsg.de/Startseite.html
     * @param Array
     * 	emptylinks - Nur leere Links suchen
     * @return Array
     */
    static function getLinksFromUrl($url, $params=array())
    {
        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

        $url = str_replace(array(
            'ä',
            'ö',
            'ü',
            'ß'
        ), array(
            '%C3%A4',
            '%C3%B6',
            '%C3%BC',
            '%C3%9F'
        ), $url);

        $_p = parse_url($url);

        if (!isset($_p['host'])) {
            $_p['host'] = '';
        }

        $host = 'http://'. $_p['host'].'/';
        $data = curl_exec($Curl);

        // Error
        if (empty($data)) {
            return array();
        }

        $result = array();

        $Dom = new DOMDocument();
        $Dom->loadHTML($data);

        // Alle Links in der Seite
        $XPath = new DOMXPath($Dom);
        $links = $XPath->evaluate("/html/body//a");

        for ($i = 0, $len = $links->length; $i < $len; $i++)
        {
            $link = $links->item($i);
            $href = $link->getAttribute('href');

            // Mails überspringen
            if (strpos($href, 'mailto:') !== false) {
                continue;
            }

            // JS überspringen
            if (strpos($href, 'javascript:') !== false) {
                continue;
            }

            // Relativen Links in absolute Umwandeln
            if (strpos($href, '/') == 0) {
                $href = $host.trim($href, '/');
            }

            if (strpos($href, '://') === false) {
                $href = $host.trim($href, '/');
            }

            $innerhtml = Utils_Dom::getInnerHTML($link);

            $result[] = array(
                'url'   => empty($href) ? false : $href,
                'empty' => empty($innerhtml) ? true : false,
                'value' => $innerhtml
            );
        }

        return $result;
    }

    /**
     * Prüft die Url
     *
     * Prüft eine URL nach Verfügbarkeit und gibt den HTTP Status zurück
     *
     * @param String $url - Zum Beispiel: http://www.pcsg.de/Startseite.html
     * @return Int
     */
    static function checkUrl($url)
    {
        $url = str_replace(' ', '+', $url); // URL Fix

        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);

        // @todo bei safe_mode geht das nicht
        //curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, true);

        // ssl
        curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($Curl, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

        $data      = curl_exec($Curl);
        $http_code = curl_getinfo($Curl, CURLINFO_HTTP_CODE );

        // timeout nicht als Fehler behandeln
        $error = curl_error($Curl);

        if ($error == 'connect() timed out' || $error == "couldn't connect to host") {
            $http_code	= 200;
        }

        // Error
        if (empty($data) && empty($http_code)) {
            $http_code	= 1000;
        }

        curl_close($Curl);

        return $http_code;
    }

    /**
     * Inhalt einer URL
     *
     * @param String $url
     */
    static function getContent($url)
    {
        $url = str_replace(' ', '+', $url); // URL Fix

        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, true);

        // ssl
        curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($Curl, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

        return curl_exec($Curl);
    }
}

?>