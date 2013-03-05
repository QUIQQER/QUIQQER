<?php

/**
 * This file contains Utils_Api_Google_YouTube
 */

/**
 * Easy Access to the YouTube Api
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.api
 * @copyright 2009 PCSG
 * @need ZendGdata
 *
 * @todo or use packagemanager
 * @todo no usage of ZendGdata
 */

class Utils_Api_Google_YouTube extends QDOM
{
    /**
     * http client
     * @var Zend_Gdata_HttpClient
     */
	private $_httpClient = null;

	/**
	 * YouTube Player Object
	 * @var Zend_Gdata_YouTube
	 */
	private $_yt = null;

	/**
	 * Upload URL
	 * @var String
	 */
	private $_upUrl = 'http://uploads.gdata.youtube.com/feeds/users/default/uploads';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$clientLibraryPath = LIB_DIR .'extern/ZendGdata/library';
		$oldPath = set_include_path(get_include_path() . PATH_SEPARATOR . $clientLibraryPath);

		require_once(LIB_DIR .'extern/ZendGdata/library/Zend/Loader.php');
		Zend_Loader::loadClass('Zend_Gdata_YouTube');
		Zend_Loader::loadClass('Zend_Gdata_AuthSub');
		Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
	}

	/**
	 * Erstellt das YouTube Objekt
	 */
	public function _getYouTube()
	{
		if (!is_null($this->_yt)) {
			return $this->_yt;
		}

		// Params
		if ($this->getAttribute('username') == false) {
			throw new QException('There was no username');
		}

		if ($this->getAttribute('password') == false) {
			throw new QException('There was no password');
		}

		if ($this->getAttribute('appid') == false) {
			throw new QException('There was no appid');
		}

		if ($this->getAttribute('clientid') == false) {
			throw new QException('There was no clientid');
		}

		if ($this->getAttribute('devkey') == false) {
			throw new QException('There was no devkey');
		}

		// Authentication
		if (is_null($this->_httpClient))
		{
			$this->_httpClient = Zend_Gdata_ClientLogin::getHttpClient(
				$username = $this->getAttribute('username'),
				$password = $this->getAttribute('password'),
				$service = 'youtube',
				$client = null,
				$source = 'GamerSpace', // a short string identifying your application
				$loginToken = null,
				$loginCaptcha = null,
				'https://www.google.com/youtube/accounts/ClientLogin'
			);
		}

		// YouTube Objekt
		$this->_yt = new Zend_Gdata_YouTube(
			$this->_httpClient,
			$this->getAttribute('appid'),
			$this->getAttribute('clientid'),
			$this->getAttribute('devkey')
		);

		return $this->_yt;
	}

	/**
	 * Ladet ein Video bei Youtube hoch
	 *
	 * @param String $file - Path to File
	 * @return Zend_Gdata_App_Entry
	 * @throws QException
	 */
	public function upload($file)
	{
		if (!file_exists($file)) {
			throw new QException('File not exist', 404);
		}

		$PT_Info = Utils_System_File::getInfo($file);

		try
		{
			$yt = $this->_getYouTube();
		} catch(Zend_Gdata_App_AuthException $e)
		{
			throw new QException(
				$e->getMessage(),
				$e->getCode()
			);
		}

		// create a new Zend_Gdata_YouTube_VideoEntry object
		$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();

		// create a new Zend_Gdata_App_MediaFileSource object
		$filesource = $yt->newMediaFileSource($file);
		$filesource->setContentType($PT_Info['mime_type']);

		// set slug header
		$filesource->setSlug($file);

		// add the filesource to the video entry
		$myVideoEntry->setMediaSource($filesource);

		$myVideoEntry->setVideoTitle($this->getAttribute('video_title'));
		$myVideoEntry->setVideoDescription($this->getAttribute('video_description'));
		$myVideoEntry->setVideoCategory($this->getAttribute('video_category')); // Note that category must be a valid YouTube category !
		$myVideoEntry->setVideoTags($this->getAttribute('video_tags'));

		if ($this->getAttribute('video_privacy')) {
			$myVideoEntry->setVideoPrivate();
		}

		// optionally set some developer tags (see Searching by Developer Tags for more details)
		//$myVideoEntry->setVideoDeveloperTags(array('mydevelopertag', 'anotherdevelopertag'));

		// optionally set the video's location
		/*
		$yt->registerPackage('Zend_Gdata_Geo');
		$yt->registerPackage('Zend_Gdata_Geo_Extension');
		$where = $yt->newGeoRssWhere();
		$position = $yt->newGmlPos('37.0 -122.0');
		$where->point = $yt->newGmlPoint($position);
		$myVideoEntry->setWhere($where);
		*/

		// try to upload the video, catching a Zend_Gdata_App_HttpException if available
		// or just a regular Zend_Gdata_App_Exception
		try
		{
			return $yt->insertEntry(
				$myVideoEntry,
				$this->_upUrl,
				'Zend_Gdata_YouTube_VideoEntry'
			);

		} catch (Zend_Gdata_App_HttpException $httpException)
		{
			throw new QException(
				$httpException->getRawResponseBody()
			);
		} catch (Zend_Gdata_App_Exception $e)
		{
			throw new QException(
				$e->getMessage(),
				$e->getCode()
			);
		}
	}
}

?>