<?php

/**
 * This file contains Utils_String
 */

mb_internal_encoding('UTF-8');

/**
 * Helper for string handling
 *
 * @author www.pcsg.de (Henning Leutz
 * @package com.pcsg.qui.utils
 *
 * @todo doku translation
 */

class Utils_String
{
    /**
     * internal string param
     * @var String
     */
	public $_string;

	/**
	 * Constructor
	 * @param String $string
	 */
	public function __construct($string)
	{
		$this->_string = (string)$string;
	}

	/**
	 * Wandelt JavaScript Strings für PHP in richtige Strings um
	 *
	 * @param String|Bool $value
	 * @return String
	 */
	static function JSString($value)
	{
		if (is_string($value)) {
			return $value;
		}

		return (string)$value;
	}

	/**
	 * Verinfachtes Pathinfo
	 *
	 * @param String $path		- path to file
	 * @param Integer $options 	- PATHINFO_DIRNAME, PATHINFO_BASENAME, PATHINFO_EXTENSION
	 * @return Array|String
	 */
	static function pathinfo($path, $options=false)
	{
		if (!file_exists($path)) {
			throw new QException('File '. $path .' not exists');
		}

		$info = pathinfo($path);

		switch ($options)
		{
			case PATHINFO_DIRNAME:
				return $info['dirname'];
			breaK;

			case PATHINFO_BASENAME:
				return $info['basename'];
			breaK;

			case PATHINFO_EXTENSION:
				return $info['extension'];
			breaK;

			case false:
			default:
				return $info;
			break;
		}
	}

	/**
	 * Entfernt doppelte Slashes und macht einen draus
	 * // -> /
	 * /// -> /
	 *
	 * @param String $path
	 * @return String
	 */
	static function replaceDblSlashes($path)
	{
		return preg_replace('/[\/]{2,}/', "/", $path);
	}

	/**
	 * Entfernt Zeilenumbrüche
	 *
	 * @param String $text
	 * @param String $replace - Mit was ersetzt werden soll
	 * @return String
	 */
	static function removeLineBreaks($text, $replace=' ')
	{
		$str = preg_replace('/([\t]){2,}/', "\t", $text);

		$str = str_replace(
			array("\r\n","\n","\r", "\t"),
			$replace,
			$str
		);

		// doppelte leerzeichen raus
		if ($replace === ' ') {
            $str = preg_replace('/([ ]){2,}/', "\t", $text);
		}

		return $str;
	}

	/**
	 * Löscht doppelte hintereinander folgende Zeichen in einem String
	 *
	 * @param String $str
	 * @return String
	 */
	static function removeDblSigns($str)
	{
        $_str = $str;

        for ($i = 0, $len = mb_strlen($str); $i < $len; $i++)
        {
            $char = mb_substr($str, $i, 1);

            if (empty($char)) {
                continue;
            }

            if ($char === '/') {
                $char = '\\'. $char;
            }

            $_str = preg_replace('/(['. $char .']){2,}/', "$1", $_str);
        }

        return $_str;
	}

	/**
	 * Entfernt den letzten Slash am Ende, wenn das letzte Zeichen ein Slash ist
	 *
	 * @param String $str
	 * @return String
	 */
	static function removeLastSlash($str)
	{
        return preg_replace(
        	'/\/($|\?|\#)/U',
        	'\1',
        	$str
        );
	}

	/**
	 * Erstes Zeichen eines Wortes gross schreiben alle anderen klein
	 *
	 * @param unknown_type $str
	 * @return unknown
	 */
	static function firstToUpper($str)
	{
		return ucfirst( self::toLower($str) );
	}

    /**
     * Schreibt den String klein
     *
     * @param unknown_type $string
     * @return String
     */
	static function toLower($string)
	{
        return mb_strtolower( $string );
	}

    /**
     * Schreibt den String gross
     *
     * @param unknown_type $string
     * @return String
     */
    static function toUpper($string)
	{
        return mb_strtoupper( $string );
	}

	/**
	 * Prüft ob der String ein Echter UTF8 String ist
	 *
	 * @param String $str
	 * @return bool
	 */
	static function isValidUTF8($str)
	{
	    $test1 = false;
	    $test2 = false;

	    if ( preg_match('%^(?:
            	  [\x09\x0A\x0D\x20-\x7E]            # ASCII
               	| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
                | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
                | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
                | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
				)*$%xs', $str)
	     )
	     {
	         $test1 = true;
	     }

        if ( !((bool)preg_match('~[\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF\xC0\xC1]~ms', $str)) ) {
            $test2 = true;
        }

        if ( $test1 && $test2 ) {
            return true;
        }

        return false;
	}

	/**
	 * Gibt einen String als UTF8 String zurück
	 *
	 * @param String $str
	 * @return String
	 */
	static function toUTF8($str)
	{
         if ( !self::isValidUTF8($str) ) {
            return utf8_encode( $str );
         }

         return $str;
	}

	/**
	 * Zählt die wichtig Wörter eines deutschen Textes
	 *
	 * @param String $text
	 * @return Array
	 */
	static function countWords($text)
	{
		$str = $text;

		// html raus
		$str = strip_tags( $str );
		$str = explode( ' ', $str );

		$result = array();

		foreach ( $str as $entry )
		{
			if ( self::filterText( $entry ) == false ) {
				continue;
			}

			// sammeln
			if ( isset( $result[$entry] ) )
			{
				$result[$entry]++;
				continue;
			}

			$result[$entry] = 1;
		}

		asort( $result );
		$result = array_reverse( $result );

		return $result;
	}

	/**
	 * Filtert einen Text von bestimmten Wörter und hohlt nur die wichtigen raus
	 *
	 * @param unknown_type $word
	 * @return unknown
	 */
	static function filterText($word)
	{
		if ( strlen($word) <= 1 ) {
			return false;
		}

		// Kleingeschriebene Wörter raus
		if ( strtolower($word{0}) == $word{0} ) {
			return false;
		}

		if ( preg_match('/[^a-zA-Z]/i', $word) ) {
			return false;
		}

		// Wörter mit 2 Grossbuchstaben
		$w0 = utf8_encode(
			strtoupper(
			    str_replace(
			        array('ä','ö','ü'),
			        array('Ä','Ö','Ü'),
			        $word{0}
		        )
	        )
		);

		$w1 = utf8_encode(
			strtoupper(
			    str_replace(
			        array('ä','ö','ü'),
			        array('Ä','Ö','Ü'),
			        $word{1}
		        )
	        )
		);

		// Wörter mit weniger als 3 Buchstaben
		if ( strlen($word) <= 3 ) {
			return false;
		}

		// Wörter welche nicht erlaubt sind
		$not = array(
			'ab', 'aus', 'von', 'an', 'auf', 'außer', 'bei', 'hinter', 'in', 'neben', 'über', 'vor', 'entlang', 'innerhalb',
			'längs', 'an', 'auf', 'bis', 'durch', 'hinter', 'in', 'nach', 'nachdem', 'zu', 'mit', 'der', 'dem', 'den', 'die',
			'das', 'dass', 'daß', 'ein', 'eine', 'einer', 'eines', 'einem', 'einen', 'wegen', 'zufolge', 'in', 'auf', 'unter',
			'über', 'da', 'dort', 'heute', 'darum', 'deshalb', 'warum', 'weshalb', 'weswegen', 'oben', 'ausgerechnet',
			'nur', 'wo', 'wann', 'wie', 'wieso', 'halt', 'übrigens', 'daran', 'dran', 'woran', 'darüber', 'drüber',
			'hierüber', 'worüber', 'gern', 'ich', 'du', 'er', 'sie', 'es', 'wir', 'mir', 'euch', 'sie', 'uns', 'eure', 'deren',
			'etwas', 'jedermann', ' paar', 'was', 'denen', 'alle', 'man', 'wer', 'für', 'um', 'binnen', 'seit', 'während',
			'angesichts', 'anlässlich', 'aufgrund', 'behufs', 'dank', 'gemäß', 'infolge', 'kraft', 'laut',
			'mangels', 'ob', 'seitens', 'seitdem', 'trotz', 'unbeschadet', 'ungeachtet', 'vermöge', 'zwecks', 'zu', 'zur',
			'zum', 'vergebens', 'fast', 'zwar', 'sehr', 'recht', 'überaus', 'folglich', 'ja', 'halt', 'eh', 'wohl', 'erstens',
			'zweitens', 'drittens', 'sogar', 'bereits', 'bedauerlicherweise', 'leider', 'sicher', 'sicherlich',
			'vielleicht', 'viel', 'viele', 'vieles', 'abzüglich', 'exklusive', 'inklusive', 'mit',
			'nebst', 'ohne', ' statt', 'anstatt', 'wider', 'wieder', 'zuwider', 'obwohl', 'wenn', 'falls', 'weil', 'bevor',
			'als', 'indem', 'und', 'weder', 'noch', 'allerdings', 'aber', 'entweder', 'oder', 'heißt', 'nämlich', 'ehe',
			'gleich', 'woher', 'wohin', 'wodurch', 'soviel', 'sowie', 'sooft', 'denn', 'nun', 'sobald', 'sodass', 'so', 'damit',
			'wird', 'werden', 'hat', 'habe', 'haben', 'hatte', 'hatten', 'doch', 'jedoch', 'kann',
			'können', 'konnte', 'konnten', 'soll', 'sollte', 'sollten', 'dazu', 'ohnehin', 'muss',
			'war', 'waren', 'machen', ' dann', 'derzeit', 'beim', 'auch', 'will', 'wollen',
			'schon', 'eher', 'lassen', 'läßt', 'lässt', 'ließ', 'lies', 'dürfen', 'darf', ' gibt',
			'geben', 'gab', 'gaben', 'zuletzt', 'also', 'davon'
		);

		$_word = strtolower($word);

		if ( in_array($_word, $not) ) {
			return false;
		}

		/*  *gegen*  */
		if ( strpos($word, 'gegen') !== false ) {
			return false;
		}

		/* search*  */
		$beginings = array(
			'außer', 'ober', 'unter,	welch', 'kein', 'hier', 'nicht', 'jede', 'manch', 'dies', 'jene', 'jemand', 'halb', 'irgend', 'nirgend', 'indes',
			'solang', 'beide', 'erste', 'zweite', 'dritte', 'vierte', 'fünfte', 'sechste', 'siebte', 'achte', 'neunte', 'zehnte', 'elfte', 'zwölfte',
			'vermittels', 'einig', 'betreff', 'ihr', 'ihn', 'mein', 'sein', 'unser', 'euer', 'dein', 'niemand', 'mittels', 'sonder', 'manch', 'mein',
			'wurde', 'musste', 'wollte', 'durfte', 'machte'
		);

		foreach ( $beginings as $search )
		{
			$pos = strpos( $_word, $search );

			if ( $pos === false ) {
				continue;
			}

			if ( $pos == 0 ) {
				return false;
			}
		}

		/* *search  */
		$endings = array(
			'mal', 'sofern', 'soweit', 'samt', 'einander',
			'schließlich', 'züglich', 'weit', 'zwischen',
			'mitten', 'jenige', 'selbe'
		);

		foreach ( $endings as $search )
		{
			if ( preg_match('/(.*?)'. $search .'$/i', $word) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Erster Satz bekommen
	 *
	 * @param String $text
	 * @return String
	 */
	static function sentence($text)
	{
		if ( strpos($text, '.') === false &&
			 strpos($text, '!') === false &&
			 strpos($text, '?') === false )
		{
			return '';
		}

		$text = preg_replace( '/(.*?[^\.|\!|\?][\.|\!|\?])(.*?)$/', '$1', $text );

		return $text;
	}

	/**
	 * Parset einen String zu einem richtigen Float Wert
	 * From php.net
	 *
	 * @param String $str
	 * @return Float
	 */
	static function parseFloat($str)
	{
	    if ( is_float($str) ) {
	        return $str;
	    }

	    if ( empty($str) ) {
			return 0;
	    }
	    // @todo lokaliesierung richtig prüfen localeconv()
		if ( strstr((string)$str, ",") )
		{
			$str = str_replace(".", "", (string)$str);
			$str = str_replace(",", ".", (string)$str);
		}

		$minus = false;

		if ( $str{0} == '-' || $str < 0 ) {
			$minus = true;
		}

		if ( preg_match("#([0-9\.]+)#", $str, $match) )
		{
			$result = floatval( $match[0] );
  		} else
  		{
  			$result = floatval( $str );
  		}


		if ( $minus && $result > 0 ) {
			return (-1)*$result;
		}

		return $result;
	}

	/**
	 * Wandelt eine Zahl in das passende Format für eine Datenbank um
	 *
	 * @param unknown_type $value
	 * @return number
	 */
	static function number2db($value)
	{
	    $larr   = localeconv();
	    $search = array(
            $larr['decimal_point'],
            $larr['mon_decimal_point'],
            $larr['thousands_sep'],
            $larr['mon_thousands_sep'],
            $larr['currency_symbol'],
            $larr['int_curr_symbol']
	    );

	    $replace = array('.', '.', '', '', '', '');

	    return str_replace($search, $replace, $value);
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $tags
	 * @param Integer $start
	 * @param Integer $min
	 *
	 * @return unknown
	 */
	static function tagCloud($tags, $start=26, $min=10)
	{
	    if ( !is_array($tags) ) {
	        $tags = array();
	    }

		for ( $i = 0, $len = count($tags); $i < $len; $i++ ) {
			$tags[$i]['count'] = $i;
		}

		shuffle($tags);

		$str = '';

	    foreach ( $tags as $entry )
	    {
			$size = $start - $entry['count'];

			if ($min > $size) {
				$size = $min;
			}

	    	$str .= '<a href="'. $entry['url'] .'" style="font-size: '. $size .'px">'. $entry['tag'] .'</a> ';
	    }

	    return $str;
	}

	/**
	 * Einzelnen Attribute einer URL bekommen
	 *
	 * @param String $url - ?id=1&project=demo
	 * @return array
	 */
	static function getUrlAttributes($url)
	{
		$url = explode( '?', $url );
		$att = array();

		if ( !isset( $url[1] ) ) {
			return $att;
		}

		$att_ = explode('&', $url[1]);

		foreach ( $att_ as $a )
		{
			$item = explode('=', $a);
			$att[ $item[0] ] = $item[1];
		}

		return $att;
	}

	/**
	 * Gibt die Attribute eines HTML Strings zurück
	 *
	 * @param String $html - <img * />
	 * @return Array
	 */
	static function getHTMLAttributes($html)
	{
		$cleaned = preg_replace('/\s+=\s+/', '=', $html);
		preg_match_all('/(?:^|\s)(\w+)="([^">]+)"/',$cleaned, $qatts);
		preg_match_all('/(?:^|\s)(\w+)=([^"\s>]+)/',$cleaned, $patts);
		$allatts = array_merge($patts[1],$qatts[1]);
		$allvals = array_merge($patts[2],$qatts[2]);

		$attributes = array();

		for ( $i = 0; $i <= count($allatts)-1; $i++ ) {
			$attributes[ $allatts[$i] ] = $allvals[$i];
		}

		return $attributes;
	}

	/**
	 * Gibt die Attribute eines HTML Styles zurück
	 *
	 * @param String $style - "width:200px; height:200px"
	 * @return Array
	 */
	static function splitStyleAttributes($style)
	{
		$attributes = array();
		$style      = str_replace( ' ', '', $style );
		$style      = explode( ';', $style );

		foreach ( $style as $att )
		{
			$att_ = explode( ':', $att );

			if ( isset($att_[0]) && isset($att_[1]) ) {
				$attributes[ strtolower( $att_[0] ) ] = $att_[1];
			}
		}

		return $attributes;
	}

	/**
	 * Replace the last occurrences of the search string with the replacement string
	 *
	 * @param String $search
	 * @param String $replace
	 * @param String $string
	 */
	static function replaceLast($search, $replace, $string)
    {
        if ( strpos($string, $search) === false ) {
            return $string;
        }

        return substr_replace(
            $string,
            $replace,
            strrpos($string, $search),
            strlen($search)
        );
    }
}

?>