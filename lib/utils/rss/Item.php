<?php

/**
 * This file contains the Utils_Rss_Item
 */

/**
 * A feed Item
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 * @package com.pcsg.qui.utils.feed
 *
 * @todo code style
 * @todo documentation in english
 */

class Utils_Rss_Item extends QDOM
{
	/**
	 * Methode zum Erstellen des XML
	 *
	 * @return String
	 */
	public function create()
	{
		switch ($this->getAttribute('type'))
		{
			case "ATOM":
				return $this->_createATOM();
			break;

			case "GoogleSitemap":
				return $this->_createGoogleSitemap();
			break;

			case "XmlSitemap":
				return $this->_createXmlSitemap();
			break;

			case "Facebook":
				return $this->_createFacebook();
			break;

			default:
				return $this->_createRSS();
			break;
		}
	}

	/**
	 * Erstellt den Eintrag als RSS
	 *
	 * @return String
	 */
	protected function _createRSS()
	{
		$url = $this->getAttribute('link');

		$entities     = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        $url          = str_replace($entities, $replacements, urlencode($url));

		$xml = '<item>
	        <title>'. htmlspecialchars($this->getAttribute('title')) .'</title>
	        <link>'. $url .'</link>
	        <author>'. htmlspecialchars($this->getAttribute('author')) .'</author>
	        <guid>'. $url .'</guid>
	        <pubDate>'. date("r", $this->getAttribute('pubDate') ) .'</pubDate>

	        <description><![CDATA['. htmlspecialchars($this->getAttribute('description')) .']]></description>
	        <content:encoded><![CDATA['. $this->getAttribute('content') .']]></content:encoded>
	    </item>';

		return $xml;
	}

	/**
	 * Erstellt den Eintrag als Atom
	 *
	 * @return String
	 */
	protected function _createATOM()
	{
		$xml = '<entry>
		    <title>'. htmlspecialchars($this->getAttribute('title')) .'</title>
		    <link href="'. $this->getAttribute('link') .'"/>
		    <author><name>'. htmlspecialchars($this->getAttribute('author')) .'</name></author>
		    <id>'. $this->getAttribute('link') .'</id>
		    <updated>'. $this->getAttribute('updated') .'</updated>
		    <summary>'. htmlspecialchars($this->getAttribute('summary')) .'</summary>
		    <content>'. htmlspecialchars($this->getAttribute('updated')) .'</content>
		</entry>';

		return $xml;
	}

	/**
	 * Erstellt den Eintrag als Google Sitemap
	 *
	 * @return String
	 */
	protected function _createGoogleSitemap()
	{
		$xml = '<url>
		    <loc>'. $this->getAttribute('loc') .'</loc>
		    <lastmod>'. $this->getAttribute('lastmod') .'</lastmod>
		</url>';

		return $xml;
	}

	/**
	 * Erstellt den Eintrag als Google Sitemap
	 *
	 * @return String
	 */
	protected function _createFacebook()
	{
		$url = $this->getAttribute('link');
		$url = explode('/', $url);

		$_url = array();

		foreach ($url as $part) {
			$_url[] = urlencode($part);
		}

		$url = implode('/', $_url);
		$url = str_replace('%3A', ':', $url);

		$xml = '<item>
	        <title><![CDATA['. htmlspecialchars($this->getAttribute('title')) .']]></title>
	        <description><![CDATA['. htmlspecialchars($this->getAttribute('description')) .'<br />'. $url .']]></description>
	        <link>'. $url .'</link>
	        <author>'. htmlspecialchars($this->getAttribute('author')) .'</author>
	        <guid>'. $url .'</guid>
	        <pubDate>'. date("r", $this->getAttribute('pubDate') ) .'</pubDate>
	    </item>';

		return $xml;
	}

	/**
	 * Erstellt ein Eintrag für eine XML Sitemap
	 *
	 * @params
	 * 	name
	 *  lang
	 *  access
	 *  genre
	 *  publication_date
	 *  title
	 *  keywords
	 *  stock_tickers
	 *
	 * @example http://www.google.com/support/webmasters/bin/answer.py?hl=de&answer=74288
	 *
	 * @return String
	 */
	protected function _createXmlSitemap()
	{
		$xml = '
		<url>
		    <loc>'. $this->getAttribute('loc') .'</loc>
		    <n:news>
		      <n:publication>
		        <n:name><![CDATA['. $this->getAttribute('name') .']]></n:name>
		        <n:language>'. $this->getAttribute('lang') .'</n:language>
		      </n:publication>';

		if ($this->getAttribute('access')) {
			$xml .= '<n:access>'. $this->getAttribute('access') .'</n:access>';
		}

		if ($this->getAttribute('genre')) {
			$xml .= '<n:genres>'. $this->getAttribute('genre') .'</n:genres>';
		}

		if ($this->getAttribute('publication_date')) {
			$xml .= '<n:publication_date>'. $this->getAttribute('publication_date') .'</n:publication_date>';
		}

		if ($this->getAttribute('title'))
		{
			$xml .= '<n:title><![CDATA[
			  '. $this->getAttribute('title') .'
			]]></n:title>';
		}

		if ($this->getAttribute('keywords')) {
			$xml .= '<n:keywords><![CDATA['. $this->getAttribute('keywords') .']]></n:keywords>';
		}

		if ($this->getAttribute('stock_tickers')) {
			$xml .= '<n:stock_tickers>'. $this->getAttribute('stock_tickers') .'</n:stock_tickers>';
		}

		$xml .= '
		   </n:news>
		</url>';

		return $xml;
	}
}

?>