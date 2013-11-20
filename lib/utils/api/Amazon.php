<?php

/**
 * This file contains Utils_Api_Amazon
 * @package com.pcsg.qui.utils.api
 */

/**
 * Amazon WebService Access
 *
 * Easy use of the Amazon API
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.api
 * @todo check it
 */

class Utils_Api_Amazon extends \QUI\QDOM
{
    /**
     * Constructor
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        if (!isset($settings['secret_key_id'])) {
            throw new \QUI\Exception('No Private Key given', 401);
        }

        if (!isset($settings['access_key_id'])) {
            throw new \QUI\Exception('No Access Key given', 401);
        }

        // default
        $this->setAttribute('service', 'AWSECommerceService');
        $this->setAttribute('host', 'ecs.amazonaws.de');
        $this->setAttribute('uri', '/onca/xml');
        $this->setAttribute('method', 'GET');

        $this->setAttribute('access_key_id', 'XXXXXX');
        $this->setAttribute('secret_key_id', 'YYYYYY');

        foreach ($settings as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Search Amazon
     *
     * @param array $params
     * @return DOMDocument
     */
    public function search(array $params)
    {
        $needle = array();
        $needle["Service"]        = "AWSECommerceService";
        $needle["AWSAccessKeyId"] = $this->getAttribute('access_key_id');

        // GMT timestamp
        $needle["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");

        // API version
        $needle["Version"]       = "2009-03-31";
        $needle["Operation"]     = "ItemLookup";
        $needle["ResponseGroup"] = "Image";
        $needle["ItemId"]        = "3836401126";

        foreach ($params as $key => $value) {
            $needle[$key] = $value;
        }

        ksort($needle);
        $params = $needle;

        $canonicalized_query = array();

        foreach ($params as $param => $value)
        {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));

            $canonicalized_query[] = $param."=".$value;
        }

        $canonicalized_query = implode("&", $canonicalized_query);

        $string_to_sign = $this->getAttribute('method')."\n".$this->getAttribute('host')."\n".$this->getAttribute('uri')."\n".$canonicalized_query;

        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->getAttribute('secret_key_id'), true));
        $signature = str_replace("%7E", "~", rawurlencode($signature));

        $url = "http://".$this->getAttribute('host').$this->getAttribute('uri')."?".$canonicalized_query."&Signature=".$signature;

        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_USERAGENT, 'GamerSpace (http://www.gamerspace.net/)');
        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

        $content = curl_exec($Curl);

        $XML = new SimpleXMLElement($content);

        if ($XML->Error)
        {
            $http_code = curl_getinfo($Curl, CURLINFO_HTTP_CODE);

            throw new \QUI\Exception(
                $XML->Error->Message,
                $http_code
            );
        }

        $DomSxe = dom_import_simplexml($XML);
        $Dom    = new DOMDocument('1.0');

        if (!$DomSxe) {
            return $Dom;
        }

        $Dom = new DOMDocument('1.0');
        $DomSxe = $Dom->importNode($DomSxe, true);
        $DomSxe = $Dom->appendChild($DomSxe);

          return $Dom;
    }

    /**
     * Parse the DOM result in an \QUI\QDOM items
     *
     * @param DOMDocument $DOM
     * @return Array
     */
    public function parse(DOMDocument $DOM)
    {
        $items = array();

        switch ($DOM->firstChild->nodeName)
        {
            case 'ItemSearchResponse':
            case 'ItemLookupResponse':
                $Items = $DOM->firstChild->getElementsByTagName('Items');
            break;

            default:
                return $items;
        }

        if ($Items == NULL) {
            return $items;
        }

        if ($Items->length == 0) {
            return $items;
        }

        $Children = $Items->item( 0 )->getElementsByTagName('Item');

        foreach ($Children as $Child)
        {
            $Attributes = $Child->getElementsByTagName('ItemAttributes')->item( 0 );
            $Item       = new \QUI\QDOM();

            if ($Attributes->firstChild)
            {
                $node = $Attributes->firstChild;

                while ($node)
                {
                    $Item->setAttribute($node->nodeName, $node->nodeValue);
                    $node = $node->nextSibling;
                }
            }

            // Attribute setzen

            if ($Child->getElementsByTagName('ASIN')->length) {
                $Item->setAttribute('ASIN', $Child->getElementsByTagName('ASIN')->item(0)->nodeValue);
            }

            // Bilder
            $ImageSets = $Child->getElementsByTagName('ImageSets');

            if ($ImageSets->length)
            {
                $SmallImage = $ImageSets->item(0)->getElementsByTagName('SmallImage');

                if ($SmallImage->length)
                {
                    $URL = $SmallImage->item(0)->getElementsByTagName('URL');
                    $Item->setAttribute('Image_Small',$URL->item(0)->nodeValue);
                }

                $MediumImage = $ImageSets->item(0)->getElementsByTagName('MediumImage');

                if ($MediumImage->length)
                {
                    $URL = $MediumImage->item(0)->getElementsByTagName('URL');
                    $Item->setAttribute('Image_Medium',$URL->item(0)->nodeValue);
                }

                $LargeImage = $ImageSets->item(0)->getElementsByTagName('LargeImage');

                if ($LargeImage->length)
                {
                    $URL = $LargeImage->item(0)->getElementsByTagName('URL');
                    $Item->setAttribute('Image_Large',$URL->item(0)->nodeValue);
                }
            }

            $Item->setAttribute('json', json_encode($Item->getAllAttributes()));
            $items[] = $Item;
        }

        return $items;
    }

    /**
     * Parse DOM ImageSets in \QUI\QDOM items
     * Only Screenshots
     *
     * @param DOMDocument $DOM
     * @return Array
     */
    public function parseImageSets(DOMDocument $DOM)
    {
        $items = array();

        switch ($DOM->firstChild->nodeName)
        {
            case 'ItemSearchResponse':
            case 'ItemLookupResponse':
                $Items = $DOM->firstChild->getElementsByTagName('Items');
            break;

            default:
                return $items;
        }

        if ($Items == NULL) {
            return $items;
        }

        if ($Items->length == 0) {
            return $items;
        }

        $Children = $Items->item( 0 )->getElementsByTagName('Item');

        foreach ($Children as $Child)
        {
            $ImageSets = $Child->getElementsByTagName('ImageSets');

            if ($ImageSets->length)
            {
                $ImageSet = $ImageSets->item(0)->getElementsByTagName('ImageSet');

                for ($i = 1, $len = $ImageSet->length; $i < $len; $i++)
                {
                    $Medium = $ImageSet->item($i)->getElementsByTagName('MediumImage');
                    $Large  = $ImageSet->item($i)->getElementsByTagName('LargeImage');

                    if ($Medium->length)
                    {
                        $_itm = new \QUI\QDOM();
                        $_itm->setAttribute('Image_Medium', $Medium->item(0)->getElementsByTagName('URL')->item(0)->nodeValue);
                        $_itm->setAttribute('Image_Large', $Large->item(0)->getElementsByTagName('URL')->item(0)->nodeValue);

                        $items[] = $_itm;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Search all Amazonlinks and converts the id to awanted  refer-id
     *
     * @param String $str - HTML or something else
     * @param String $aid  - Amazon referal ID
     * @return String
     */
    static function parseLinksFromString($str, $aid)
    {
        global $__aid__;

        $__aid__ = $aid;

        return preg_replace_callback(
            '#(href)="([^"]*)"#',
            function($params)
            {
                if (!isset($params[0])) {
                    return $params;
                }

                if (strpos($params[0], 'href') === false ||
                    strpos($params[0], 'amazon') === false ||
                    strpos($params[0], 'http://') === false)
                {
                    return $params[0];
                }

                global $__aid__;

                $url   = parse_url($params[0]);
                $query = explode('&', trim($url['query'], '&?'));

                foreach ($query as $key => $entry)
                {
                    if (strpos($entry, 'tag=') !== false) {
                        $query[$key] = 'tag='. $__aid__;
                    }
                }

                $query = implode('&', $query);

                if (strpos($query, 'tag=') === false) {
                    $query .= 'tag='. $__aid__;
                }

                return 'href="http:'. $url['path'] .'?'. $query .'"';
            },
            $str
        );
    }
}

?>