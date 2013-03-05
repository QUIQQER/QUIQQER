<?php

/**
 * This file contains Utils_Api_Ebay
 */

/**
 * Easy access to the eBay API
 *
 * @author www.pcsg.de (Moritz Scholz)
 * @author www.pcsg.de (Hening Leutz)
 * @package com.pcsg.qui.utils.api
 *
 * @copyright PCSG
 *
 * @example $Ebay = new Utils_Api_Ebay($appID)
 *
 * @Settings
 * 	$Ebay->setAttribute('ebaySrc', 'http://open.api.ebay.com/shopping');
 *	$Ebay->setAttribute('maxEntries', 50);
 *	$Ebay->setAttribute('itemSort', 'EndTime');
 *	$Ebay->setAttribute('itemType', 'AllItems');
 *	$Ebay->setAttribute('trackingPartnercode', '601');
 *	$Ebay->setAttribute('trackingId', '601');
 *	$Ebay->setAttribute('affiliateUserId', '601');
 *
 * @infos about ebay API
 *
 * Get AppID http://developer.ebay.com/
 * API Referen = http://developer.ebay.com/DevZone/shopping/docs/CallRef/index.html
 *
 * @requires Utils_Request_Url
 * @uses Utils_Request_Url
 */

class Utils_Api_Ebay extends QDOM
{
    /**
     * internal api ebay url, to call ebay api
     * @var String
     */
	private $_url = _data;

	/**
	 * Ebay Articles
	 * @var array
	 */
	private $_items = array();

	/**
	 * request data
	 * @var Object
	 */
	private $_data = null;

	/**
	 * constructor
	 *
	 * @param array $appID
	 */
	public function __construct($appID=NULL)
	{
		if (!$appID) {
			throw new QException('No Access Ebay appId given', 401);
		}

		// default Daten setzen
		$this->setAttribute('appId', $appID);
		$this->setAttribute('siteId', 77);
		$this->setAttribute('ebaySrc', 'http://open.api.ebay.com/shopping');
		$this->setAttribute('maxEntries', 20);
		$this->setAttribute('itemSort', 'EndTime');
		$this->setAttribute('itemType', 'AllItems');
		$this->setAttribute('callname', 'FindItemsAdvanced');
		$this->setAttribute('APIversion', '601');
        $this->setAttribute('trackingPartnercode', '9');
        $this->setAttribute('trackingId', '5336210971');
        $this->setAttribute('affiliateUserId', '5336210971');
	}

	/**
	 * initializing via API Key
	 *
	 * @param Array $params - array('appId' => $appID)
	 * @return Utils_Api_Ebay
	 */
	static function init($params)
	{
	    if (!isset($params['appId'])) {
            throw new QException('No Access Ebay appId given', 401);
	    }

        $Ebay = new Utils_Api_Ebay($params['appId']);

        foreach ($params as $key => $value) {
            $Ebay->setAttribute($key, $value);
        }

        return $Ebay;
	}

    /**
     * Checks if the username already exists
     *
     * @param String $username
     * @return Bool
     */
	public function userExist($username)
	{
        $data = Utils_Request_Url::get(
    	    $this->getAttribute('ebaySrc') .'?'. http_build_query(array(
    	        'callname' => $this->getAttribute('callname'),
    			'version'  => $this->getAttribute('APIversion'),
    			'appid'    => $this->getAttribute('appId'),
    			'siteid'   => $this->getAttribute('siteId'),
    			'SellerID' => $username,
    			'responseencoding' => 'JSON'
    	    ))
    	);

    	$data = json_decode($data, true);

    	if (isset($data['Ack']) && $data['Ack'] == 'Success') {
            return 1;
    	}

    	return 0;
	}

	/**
	 * Generates the ebay request URL
	 *
	 * @return Bool
	 */
	private function makeUrl()
	{
		$_url = $this->getAttribute('ebaySrc')
            .'?callname='.$this->getAttribute('callname')
			.'&version='.$this->getAttribute('APIversion')
			.'&appid='.$this->getAttribute('appId')
			.'&siteid='.$this->getAttribute('siteId');

		if ($this->getAttribute('SellerId')) { $_url .= '&SellerID='.$this->getAttribute('SellerId'); }
		if ($this->getAttribute('maxEntries')) { $_url .= '&MaxEntries='.$this->getAttribute('maxEntries'); }
		if ($this->getAttribute('pageNumber')) { $_url .= '&MaxEntries='.$this->getAttribute('pageNumber'); }
		if ($this->getAttribute('itemSort')) { $_url .= '&ItemSort='.$this->getAttribute('itemSort'); }
		if ($this->getAttribute('itemType')) { $_url .= '&ItemType='.$this->getAttribute('itemType'); }
		if ($this->getAttribute('trackingPartnercode')) { $_url .= '&trackingpartnercode='.$this->getAttribute('trackingPartnercode'); }
		if ($this->getAttribute('trackingId')) { $_url .= '&trackingid='.$this->getAttribute('trackingId'); }
		if ($this->getAttribute('affiliateUserId')) { $_url .= '&affiliateuserid='.$this->getAttribute('affiliateUserId'); }

		$_url .= '&responseencoding=JSON';

        $this->_url = $_url;

        return true;
	}

	/**
	 * Get all items by a seller
	 *
	 * 	@tutorial Attribute die nach dem Request gesetzt werden und dann zur verfügung stehen
 	 *  	$ebay->getAttribute('TotalItems') Count der ergebnisse
     *  	$ebay->getAttribute('PageNumber') Aktuelle Seite
     *  	$ebay->getAttribute('TotalPages') Count der Seiten
	 *  	$ebay->getAttribute('ItemSearchURL') Ebay URL der Suchergebnisse
 	 * 		$ebay->getAttribute('Timestamp') Ebay Timestamp
	 *
	 * @return array
	 */
	public function getItemsBySeller()
	{
		if (!$this->getAttribute('SellerId')) {
			throw new QException('Setup a EBay SellerId before using this Function', 400);
		}

		$this->setAttribute('callname', 'FindItemsAdvanced');
		$this->makeUrl();
		$this->getEbayData();
		$this->makeItems();

		$this->setAttribute('TotalItems',$this->_data->TotalItems);
	   	$this->setAttribute('PageNumber',$this->_data->PageNumber);
	    $this->setAttribute('TotalPages',$this->_data->TotalPages);
		$this->setAttribute('ItemSearchURL',$this->_data->ItemSearchURL);
	 	$this->setAttribute('Timestamp',$this->_data->Timestamp);

		return $this->_items;
	}

	/**
	 * created the item from ebay result
	 * @return Array
	 */
	private function makeItems()
	{
		$itemData = $this->_data->SearchResult[0]->ItemArray;
		$this->_items = NULL;

		foreach ($itemData->Item as $item)
		{
			$this->_items[] = array(
                'itemId'      => $item->ItemID,
    			'title'       => $item->Title,
				'endTime'     => $item->EndTime,
    			'url'         => $item->ViewItemURLForNaturalSearch,
    			'listingType' => $item->ListingType,
    			'imageUrl'    => $item->GalleryURL,
    			'primaryCategoryId'   => $item->PrimaryCategoryID,
    			'primaryCategoryName' => $item->PrimaryCategoryName,
    			'bidCount'            => $item->BidCount,
    			'timeLeft'            => $this->getPrettyTimeFromEbayTime($item->TimeLeft),
    			'price' => array(
			        'value'    => Utils_Convert::formPrice($item->ConvertedCurrentPrice->Value,3),
    				'currency' => $item->ConvertedCurrentPrice->CurrencyID
			    ),
    			'shippingCost' => array(
    				'value'    => Utils_Convert::formPrice($item->ShippingCostSummary->ShippingServiceCost->Value,3),
    				'currency' => $item->ShippingCostSummary->ShippingServiceCost->CurrencyID
			    )
			);
		}

		return $this->_items;
	}

	/**
	 * Exceute the ebay API call
	 * @return Bool
	 */
	private function getEbayData()
	{
		$get  = file_get_contents($this->_url);
		$data = json_decode($get);

        if ($data->TotalItems == 0) {
            throw new QException('Keine Auktionen gefunden',400);
        }

		if ($data->Ack != 'Success')
		{
			$error = '';

			foreach ($data->Errors as $err) {
				$error .= $err->ErrorClassification .' - Code:'. $err->ErrorCode .' Messege:'. $err->LongMessage ."\\n";
			}

			throw new QException($error, 400);
		}

		$this->_data = $data;
		return true;
	}

	/**
	 * date formatting
	 *
	 * @param $eBayTimeString Input is of form 'PT12M25S'
	 * @return string
	 */
	private function getPrettyTimeFromEbayTime($eBayTimeString)
	{
        $matchAry = array();
        $pattern  = "#P([0-9]{0,3}D)?T([0-9]?[0-9]H)?([0-9]?[0-9]M)?([0-9]?[0-9]S)#msiU";

        preg_match($pattern, $eBayTimeString, $matchAry);
        $days  = (int)$matchAry[1];
        $hours = (int)$matchAry[2];
        $min   = (int)$matchAry[3];
        $sec   = (int)$matchAry[4];

        $retnStr = '';

        if ($days)  {
            $retnStr .= "$days Tag(e)"    . $days;
        }

        if ($hours) {
            $retnStr .= " $hours Stunde(n)" . $hours;
        }

        if ($min) {
            $retnStr .= " $min Minute(n)" . $min;
        }

        if ($sec) {
            $retnStr .= " $sec Sekunde(n)" . $sec;
        }

        return $retnStr;
    }
}

?>