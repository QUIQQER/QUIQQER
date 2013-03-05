<?php

/**
 * This file contains Utils_Convert
 */

/**
 * Convert class, helper for converting different values
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils
 */

class Utils_Convert
{
	/**
	 * Format a price
	 *
	 * 1.000,00 - 1000,00
	 *
	 * @param Integer $price
	 * @param Integer $type -
	 * 	1=round($betrag, 2)
	 * 	2=$price value with , as decimal separator
	 *  3=$price value with . as decimal separator
	 *
	 * @return String
	 */
	static function formPrice($price, $type=1)
	{
		switch ($type)
		{
			case 2:
				$price = number_format(round($price, 2), '2', ',', '.');
			break;

			case 3:
				$price = number_format(round($price, 2), '2', '.', ',');
			break;

			default:
				$price = round($price, 2);
			break;
		}

		return $price;
	}

	/**
	 * Converts some Umlauts
	 *
	 * @param String $conv
	 * @return String
	 */
	static function convertChars($conv)
	{
		$conv = str_replace("Ä", chr(196), $conv);
		$conv = str_replace("ä", chr(228), $conv);
		$conv = str_replace("Ö", chr(214), $conv);
		$conv = str_replace("ö", chr(246), $conv);
		$conv = str_replace("Ü", chr(220), $conv);
		$conv = str_replace("ü", chr(252), $conv);
		$conv = str_replace("ß", chr(223), $conv);
		$conv = str_replace("'", chr(39),  $conv);
		$conv = str_replace("´", chr(180), $conv);
		$conv = str_replace("`", chr(96),  $conv);

		return $conv;
	}

	/**
	 * Converts a MySQL DateTime format to a Unix timestamp
	 *
	 * @param String $str
	 * @return Integer
	 */
	static function convertMySqlDatetime($str)
    {
	    list($date, $time)            = explode(' ', $str);
	    list($year, $month, $day)     = explode('-', $date);
	    list($hour, $minute, $second) = explode(':', $time);

	    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

	    return $timestamp;
    }

	/**
	 * Convert umlauts e.g. ä to ae, u in ue etc.
	 * it used to url converting
	 *
	 * @param String $conv
	 * @param Integer $code 0=encode 1=decode, standard=0
	 * @return String
	 */
	static function convertUrlChars($conv, $code=0)
	{
		if ($code == 0)
		{
			$conv = str_replace("Ä", "Ae", $conv);
			$conv = str_replace("ä", "ae", $conv);
			$conv = str_replace("Ö", "Oe", $conv);
			$conv = str_replace("ö", "oe", $conv);
			$conv = str_replace("Ü", "Ue", $conv);
			$conv = str_replace("ü", "ue", $conv);
			$conv = str_replace("ß", "sz", $conv);

			return $conv;
		}

		$conv = str_replace("Ae", "Ä", $conv);
		$conv = str_replace("ae", "ä", $conv);
		$conv = str_replace("Oe", "Ö", $conv);
		$conv = str_replace("oe", "ö", $conv);
		$conv = str_replace("Ue", "Ü", $conv);
		$conv = str_replace("ue", "ü", $conv);
		$conv = str_replace("sz", "ß", $conv);

		return $conv;
	}
}

?>