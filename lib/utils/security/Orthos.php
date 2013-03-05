<?php

/**
 * This file contains the Utils_Security_Orthos
 */

/**
 * Orthos - Security class
 *
 * Has different methods in order to examine variables on their correctness
 * Should be used to validate user input
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 *
 * @package com.pcsg.qui.utils.security
 *
 * @copyright  2008 PCSG
 * @version    1.0
 */

class Utils_Security_Orthos
{
	/**
	 * Befreit einen String von allem möglichen Schadcode
	 *
	 * @param String $str
	 * @return String
	 */
	static function clear($str)
	{
		$str = self::removeHTML($str);
		$str = self::clearPath($str);
		$str = self::clearFormRequest($str);

		$str = htmlspecialchars($str);

		return $str;
	}

	/**
	 * Befreit ein Array von allem möglichen Schadcode
	 *
	 * @param Array $data
	 * @return Array
	 */
	static function clearArray($data)
	{
	    if (!is_array($data)) {
            return array();
	    }

		$cleanData = array();

		foreach ($data as $key => $str)
		{
			if (is_array($data[$key]))
			{
				$cleanData[$key] = self::clearArray($data[$key]);
				continue;
			}

			$cleanData[$key] = self::clear($str);
		}

		return $cleanData;
	}

	/**
	 * Säubert eine Pfadangabe von eventuellen Änderungen des Pfades
	 *
	 * @param String $path
	 * @return String
	 */
	static function clearPath($path)
	{
		return str_replace(array('../', '..'), '', $path);
	}

	/**
	 * Enfernt HTML aus dem Text
	 *
	 * @param unknown_type $text
	 * @return unknown
	 */
	static function removeHTML($text)
	{
		return strip_tags($text);
	}

	/**
	 * Befreit einen MySQL Command String von Schadcode
	 *
	 * @param String $str - Command
	 * @param Bool $escape - Escape the String (true or false}
	 * @return String
	 *
	 * @deprecated use PDO::quote (QUI::getPDO()->quote())
	 */
	static function clearMySQL($str, $escape=true)
	{
		if (get_magic_quotes_gpc()) {
			$str = stripslashes($str);
        }

        if ($escape) {
            $str = QUI::getPDO()->quote($str);
        }

		return $str;
	}

	/**
	 * Befreit einen Shell Command String von Schadcode
	 *
	 * @param String $str - Command
	 * @return String
	 */
	static function clearShell($str)
	{
	    return escapeshellcmd($str);
	}

	/**
	 * Enter description here...
	 *
	 * @param String $str
	 * @return Integer
	 */
	static function parseInt($str)
	{
		return (int)$str;
	}

	/**
	 * Säubert "böses" HTML raus
	 * Zum Beispiel für Wiki
	 *
	 * @param String $str
	 * @return String
	 */
	static function cleanHTML($str)
	{
		$BBCode = new Utils_Text_BBCode();

		$str = $BBCode->parseToBBCode($str);
		$str = $BBCode->parseToHTML($str);

		return $str;
	}

	/**
	 * Prüft Datumsteile nach Korrektheit
	 * Bei Korrektheit kommt $val wieder zurück ansonsten 0
	 *
	 * @param Integer $val
	 * @param String $type - DAY | MONTH | YEAR
	 *
	 * @return Integer
	 */
	static function date($val, $type)
	{
		switch ($type)
		{
			default:
			case 'DAY':

				$val = self::parseInt($val);

				// Wenn Tag nicht zwischen 1 und 31 liegt
				if ($val < 1 || $val > 31) {
					return 0;
				}

				return $val;

			break;

			case 'MONTH':
				$val = self::parseInt($val);

				// Wenn Monat nicht zwischen 1 und 12 liegt
				if ($val < 1 || $val > 12) {
					return 0;
				}

				return $val;
			break;

			case 'YEAR':
				$val = self::parseInt($val);

				// Wenn Jahr nicht eine Länge von 4 hat
				if (!is_numeric($val) || strlen($val) != 4) {
					return 0;
				}

				return $val;
			break;
		}
	}

	/**
	 * Prüft ein Datum auf Korrektheit
	 *
	 * @param Integer $day
	 * @param Integer $month
	 * @param Integer $year
	 * @return Bool
	 */
	static function checkdate($day, $month, $year)
	{
		if (!is_int($day)) {
			return false;
		}

		if (!is_int($month)) {
			return false;
		}

		if (!is_int($year)) {
			return false;
		}

		return checkdate($month, $day, $year);
	}

	/**
	 * use Utils_String::removeLineBreaks
	 * @see Utils_String::removeLineBreaks
	 * @deprecated use Utils_String::removeLineBreaks
	 * @param String $text
	 */
	static function removeLineBreaks($text)
	{
		return Utils_String::removeLineBreaks($text, ' ');
	}

    /**
     * Prüft eine Mail Adresse auf Syntax
     *
     * @param String $email - Mail Adresse
     * @return Bool
     */
    static function checkMailSyntax($email)
    {
	    return preg_match('/^([A-Za-z0-9\.\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]){1,64}\@{1}([A-Za-z0-9\.\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]){1,248}\.{1}([a-z]){2,6}$/', $email);
    }

    /**
     * Prüft ein MySQL Timestamp auf Syntax
     *
     * @param String $date
     * @return Bool
     */
    static function checkMySqlDatetimeSyntax($date)
    {
    	// Nur Zahlen erlaubt
   		if ( preg_match( '/[^0-9- :]/i', $date ) ) {
   			return false;
		}

		// Syntaxprüfung
    	if ( !preg_match( "/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $date ) ) {
    		return false;
    	}

    	return true;
    }

    /**
     * Generiert einen Zufallsstring
     *
     * @param $length - Länge des Passwortes
     * @return String
     */
    static function getPassword($length=10)
    {
	    $newpass = "";
	    $laenge  = $length;
	    $string  = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

	    mt_srand((double)microtime()*1000000);

	    for ($i = 1; $i <= $laenge; $i++) {
	        $newpass .= substr($string, mt_rand(0, strlen($string)-1), 1);
	    }

	    return $newpass;
    }

    /**
     * Prüft ob die Mail Adresse eine Spam Wegwerf Mail Adresse ist
     *
     * @param String $mail - E-Mail Adresse
     * @return Bool
     */
    static function isSpamMail($mail)
    {
		$split = explode('@', $mail);

		$adresses = array(
			'anonbox.net',
			'bumpymail.com',
			'centermail.com',
			'centermail.net',
			'discardmail.com',
			'emailias.com',
			'jetable.net',
			'mailexpire.com',
			'mailinator.com',
			'messagebeamer.de',
			'mytrashmail.com',
			'trash-mail.de',
			'trash-mail.com',
			'trashmail.net',
			'pookmail.com',
			'nervmich.net',
			'netzidiot.de',
			'nurfuerspam.de',
			'mail.net',
			'privacy.net',
			'punkass.com',
			'sneakemail.com',
			'sofort-mail.de',
			'spamex.com',
			'spamgourmet.com',
			'spamhole.com',
			'spaminator.de',
			'spammotel.com',
			'spamtrail.com',
			'temporaryinbox.com',
			'put2.net',
			'senseless-entertainment.com',
			'dontsendmespam.de',

			'spam.la',
			'spaml.de',
			'spambob.com',
			'kasmail.com',
			'dumpmail.de',
			'dodgeit.com',

			'fastacura.com',
			'fastchevy.com',
			'fastchrysler.com',
			'fastkawasaki.com',
			'fastmazda.com',
			'fastmitsubishi.com',
			'fastnissan.com',
			'fastsubaru.com',
			'fastsuzuki.com',
			'fasttoyota.com',
			'fastyamaha.com',

			'nospamfor.us',
			'nospam4.us',

			'trashdevil.de',
			'trashdevil.com',

			'spoofmail.de',
			'fivemail.de',
			'giantmail.de'
		);

		if (!isset($split[1])) {
			return false;
		}

		foreach ($adresses as $entry)
		{
			if (strpos($split[1], $entry) !== false) {
				return true;
			}
		}

    	return false;
    }

	/**
	 * Löscht alle Zeichen aus einem Request welches für ein XSS verwendet werden könnte
	 *
	 * @param String $value
	 * @return String
	 */
	static function clearFormRequest($value)
	{
		if (is_array($value))
		{
			foreach ($value as $key => $entry) {
				$value[$key] = self::clearFormRequest($entry); // htmlspecialchars_decode($entry);
			}
		} else
		{
			$value = htmlspecialchars_decode($value);
		}
        // alle zeichen undd HEX codes werden mit leer ersetzt
		$value = str_replace(
			array(
				'<', '%3C',
				'>', '%3E',
				'"', '%22',
				'\\', '%5C',
				'/', '%2F',
				'\'', '%27',
			),
			'',
			$value
		);

		return $value;
	}

	/**
	 * Verschlüsselung auf Basis des CMS Salt
	 *
	 * @param String $str
	 * @return String
	 */
	static function encrypt($str)
	{
		return Utils_Security_Encryption::blowfishEncrypt(
		    $str,
		    QUI::conf('globals', 'salt')
        );
	}

	/**
	 * Entschlüsselung auf Basis des CMS Salt
	 *
	 * @param String $str
	 * @return String
	 */
	static function decrypt($str)
	{
		return Utils_Security_Encryption::blowfishDecrypt(
		    $str,
		    QUI::conf('globals', 'salt')
        );
	}
}

?>