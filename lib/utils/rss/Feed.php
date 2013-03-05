<?php

/**
 * This file contains the Utils_Rss_Feed
 */

/**
 * Feed class
 *
 * Creates the output for a feed and can import external feeds
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 *
 * @package com.pcsg.qui.utils.feed
 *
 * @copyright  2008 PCSG
 * @version    0.6
 * @since      Class available since Release QUIQQER 0.12
 *
 * @uses SimpleXmlElement
 * @uses Curl - if Curl exist
 *
 * @todo code style
 * @todo documentation in english
 */

class Utils_Rss_Feed extends QDOM
{
	const USER_AGENT = 'PCSG RSS FeedReader (http://www.pcsg.net/)';

	/**
	 * feed items
	 * @var array
	 */
	protected $_items = array();

	/**
	 * Ein Kind hinzufügen
	 *
	 * @param Utils_Feed_Item $Itm
	 */
	public function appendChild($Itm)
	{
		$this->_items[] = $Itm;
	}

	/**
	 * Feed leeren
	 */
	public function clear()
	{
	    $this->_items = array();
	}

	/**
	 * Erstellt die Ausgabe
	 */
	public function create()
	{
		$type = 'rss';

		if ($this->getAttribute('type')) {
			$type = $this->getAttribute('type');
		}

		$channel = '';

		foreach ($this->_items as $item)
		{
			$item->setAttribute('type', $type);
			$channel .= $item->create();
		}

		$feed = '';

		switch ($type)
		{
			default:
				header('Content-Type: application/rss+xml');

				$feed = '<?xml version="1.0" encoding="utf-8"?>
					<rss version="2.0"
						xmlns:content="http://purl.org/rss/1.0/modules/content/"
						xmlns:wfw="http://wellformedweb.org/CommentAPI/"
						xmlns:dc="http://purl.org/dc/elements/1.1/"
						xmlns:atom="http://www.w3.org/2005/Atom"
						xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
						xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
					>' .
                        $channel .
    				'</rss>';
			break;

			case "ATOM":
			case "Facebook":
			case "XmlSitemap":
			case "GoogleSitemap":
				header('Content-type: application/xml; charset=utf-8');
				$feed = '<?xml version="1.0" encoding="UTF-8"?>'. $channel;
			break;
		}

		return $feed;
	}

	/**
	 * Prüft einen RSS Feed auf Verfügbarkeit
	 *
	 * @param String $feedurl
	 * @return bool
	 * @throws QException
	 */
	static function check($feedurl)
	{
		$httpcode = Utils_Request_Linkchecker::checkUrl($feedurl);

		if ($httpcode >= 400) {
			throw new QException('Cant connect to Feed', $httpcode);
		}

		return true;
	}

	/**
	 * Hohlt sich einen Feed von einer bestimmten URL
	 *
	 * @param String $feedurl
	 * @return SimpleXmlElement
	 */
	static function getFeedFromUrl($feedurl)
	{
		if (function_exists('curl_init'))
		{
			$Curl = curl_init();
			curl_setopt($Curl, CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($Curl, CURLOPT_URL, $feedurl);
			curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($Curl, CURLOPT_FRESH_CONNECT, true);

			curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

			$raw = curl_exec($Curl);

		} else
		{
			$raw = file_get_contents($feedurl);
		}

		// Damit der Feed auch wirklich eingelesen werden kann
		$raw = preg_replace("/(>)(.*?)(<)/ise", '"$1". htmlspecialchars("$2") ."$3"', $raw);

		return new SimpleXmlElement($raw);
	}

	/**
	 * Gibt Channel Informationen von einem Feed zurück
	 *
	 * @param String $feedurl
	 * @return Array
	 */
	static function getChannelInformationFromFeed($feedurl)
	{
		$Xml = self::getFeedFromUrl($feedurl);

		if (empty($Xml) || !$Xml->channel) {
			return array();
		}

		return array(
			'title'       => $Xml->channel->title,
			'link'        => $Xml->channel->link,
			'description' => $Xml->channel->description,
			'pubDate'     => $Xml->pubDate,
			'timestamp'   => strtotime($Xml->pubDate),
			'generator'   => $Xml->generator,
			'language'    => $Xml->language
		);
	}

	/**
	 * Gibt die Artikel vom RSS Feed zurück
	 *
	 * @param String $feedurl
	 * @return Array
	 */
	static function getArticlesFromFeed($feedurl)
	{
		$Xml = self::getFeedFromUrl($feedurl);

		if (empty($Xml) || !$Xml->channel) {
			return array();
		}


		$Items = $Xml->channel->children();

		$ns = array (
        	'content' => 'http://purl.org/rss/1.0/modules/content/',
        	'wfw'     => 'http://wellformedweb.org/CommentAPI/',
        	'dc'      => 'http://purl.org/dc/elements/1.1/'
		);

		$articles = array();

		foreach ($Items as $Item)
		{
			if ($Item->getName() != 'item') {
				continue;
			}

			$article = array(
				'channel'     => $Xml->channel->title,
				'title'       => $Item->title,
				'link'        => $Item->link,
				'comments'    => $Item->comments,
				'pubDate'     => $Item->pubDate,
				'timestamp'   => strtotime($Item->pubDate),
				'description' => (string) trim($Item->description),
				'isPermaLink' => isset($Item->guid['isPermaLink']) ? $Item->guid['isPermaLink'] : ''
			);

			// get data held in namespaces
			$content = $Item->children($ns['content']);
			$dc      = $Item->children($ns['dc']);
			$wfw     = $Item->children($ns['wfw']);

			$article['creator'] = (string)$dc->creator;

			foreach ($dc->subject as $subject) {
				$article['subject'][] = (string)$subject;
			}

			$article['content']    = (string)trim($content->encoded);
			$article['commentRss'] = $wfw->commentRss;

			// add this article to the list
			if (!isset($article['timestamp']) || empty($article['timestamp']))
			{
				$articles[] = $article;
				continue;
			}

			if (isset($articles[ $article['timestamp'] ]))
			{
				$articles[] = $article;
				continue;
			}

			$articles[ $article['timestamp'] ] = $article;
		}

		return $articles;
	}
}

?>