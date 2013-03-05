<?php

/**
 * This file contains Utils_Text_HtmlToText
 */

/**
 * Converts HTML to text
 * for example : use for mail delivery
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.text
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 3400 $
 * @since      Class available since Release QUIQQER 0.11
 */

class Utils_Text_HtmlToText extends QDOM
{
    /**
     * The html string
     * @var String
     */
	private $_HTML = '';

	/**
	 * Set the HTML Text
	 * Working with this text
	 *
	 * @param String $html
	 */
	public function setHTML($html)
	{
		$this->_HTML = $html;
	}

	/**
	 * Return the HTML as plain text
	 *
	 * @return String
	 */
	public function getText()
	{
		$text = $this->_HTML;

		// Alle Linebreaks und doppelten Leerzeichen raus
		$text = preg_replace(array(
			"/\r/",
        	//"/[\n\t]+/",
        	'/[ ]{2,}/'
        ), ' ', $text);

        // Head raus

        $text = preg_replace(
            array(
            	// Remove invisible content
            	'@<head[^>]*?>.*?</head>@siu',
            	'@<style[^>]*?>.*?</style>@siu',
            	'@<script[^>]*?.*?</script>@siu',
            	'@<object[^>]*?.*?</object>@siu',
            	'@<embed[^>]*?.*?</embed>@siu',
            	'@<applet[^>]*?.*?</applet>@siu',
            	'@<noframes[^>]*?.*?</noframes>@siu',
            	'@<noscript[^>]*?.*?</noscript>@siu',
            	'@<noembed[^>]*?.*?</noembed>@siu'
            ),

        	array(
            	' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '
        	),
        	$text
        );

        // HTML Zeichen zurück zu normalen Zeichen
        $text = htmlspecialchars_decode($text);
        $text = str_replace('&nbsp;', ' ', $text);

		// Standard Elemente umwandeln
		$text = preg_replace(
			array(
				'/<h[123][^>]*>(.*?)<\/h[123]>/ie',		// H1 - H3
		        '/<h[456][^>]*>(.*?)<\/h[456]>/ie',		// H4 - H6
		        '/<p[^>]*>/i',							// <p>
		        '/<br[^>]*>/i',							// <br>
		        '/<b[^>]*>(.*?)<\/b>/ie',				// <b>
		        '/<strong[^>]*>(.*?)<\/strong>/ie',		// <strong>
		        '/<i[^>]*>(.*?)<\/i>/i',				// <i>
		        '/<em[^>]*>(.*?)<\/em>/i',				// <em>
		        '/(<ul[^>]*>|<\/ul>)/i',				// <ul>
		        '/(<ol[^>]*>|<\/ol>)/i',				// <ol>
		        '/<li[^>]*>(.*?)<\/li>/i',				// <li>
				'/<hr[^>]*>/i'							// <hr>
			),
			array(
				"strtoupper(\"\n\n\\1\n\n\")",			// H1 - H3
		        "ucwords(\"\n\n\\1\n\n\")",				// H4 - H6
		        "\n\n\t",								// <p>
		        "\n",									// <br>
		        'strtoupper("\\1")',					// <b>
		        'strtoupper("\\1")',					// <strong>
		        '_\\1_',								// <i>
		        '_\\1_',								// <em>
		        "\n\n",									// <ul>
		        "\n\n",									// <ol>
		        "\t* \\1\n",							// <li>
				"\n-------------------------\n"			// <hr>
			),
			$text
		);


		// Tabellen umwandeln
		$text = preg_replace(
			array(
		      	'/(<table[^>]*>)/i',			// <table> and </table>
				'/(<\/table>)/i',			// <table> and </table>

		      	'/(<tr[^>]*>|<\/tr>)/i',				// <tr> and </tr>
		        '/<td[^>]*>(.*?)<\/td>/i',				// <td> and </td>
		        '/<th[^>]*>(.*?)<\/th>/ie',				// <th> and </th>
			),
			array(
				"\n+-------------------------+\n",		// <table> and </table>
		        "\n+-------------------------+\n",

				"",									// <tr> and </tr>
		        "\t\\1\n",							// <td> and </td>
		        "strtoupper(\"\t\\1\n\")",			// <th> and </th>
			),
			$text
		);

		$text = preg_replace_callback(
			'#<a([^>]*)>(.*?)<\/a>#is',
			array(&$this, "_outputlink"),
			$text
		);

		// restliches HTML raus
		$text = strip_tags($text);

		// Doppelte Umbrüche und Tabs nun zu einem
		$text = preg_replace('/\t/', "", $text);
		$text = preg_replace('/\r/', "", $text);

		$text = preg_replace('/[ ]{2,}/', " ", $text);
		$text = preg_replace('/[\n \n]{2,}/', "\n\n", $text);
		$text = preg_replace('/[\n]{3,}/', "\n\n", $text);

		$text = str_replace('+-------------------------+', '', $text);

		return $text;
	}

	/**
	 * Parse all links to text links
	 *
	 * @param String $params
	 * @return String
	 */
	private function _outputlink($params)
	{
		$attributes = str_replace('\"', '"', $params[1]);
		$url = preg_replace('/(.*?)href="([^"]+).*"/is', '\\2', $attributes);

		return $params[2].'('. $url .')';
	}

}

?>