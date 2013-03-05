<?php

/**
 * This file contains the Utils_Security_Encryption
 */

/**
 * Main QUIQQER Encryption class
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.security
 */

class Utils_Security_Encryption extends QDOM
{
	/**
	 * Encrypted a password base64
	 *
	 * @param String $pass		- String to encrpyted
	 * @param Integer $switch	- where to split
	 *
	 * @return String
	 */
	static function encrypt($pass, $switch=3)
	{
		// Passwort drehn
		$newpass = substr($pass, 3) . substr($pass, 0, 3);
		return base64_encode($newpass);
	}

	/**
	 * Decrypt a base64 password
	 *
	 * @param String $pass	  - String to decrypt
	 * @param Integer $switch - where to split
	 *
	 * @return String
	 */
	static function decrypt($pass, $switch=3)
	{
		$newpass = base64_decode($pass);
		return substr($newpass, -3) . substr($newpass, 0, -3);
	}

	/**
	 * Blowfish encryption
	 *
	 * @param String $str - String to encrypted
	 * @param String $key - Blowfish key
	 *
	 * @return String
	 */
	static function blowfishEncrypt($str, $key)
	{
		require_once dirname(__FILE__) .'/blowfish/blowfish.class.php';

		$Blowfish = new Blowfish($key);
		return $Blowfish->Encrypt($str);
	}

	/**
	 * Blowfish decryption
	 *
	 * @param String $str - String to decrypted
	 * @param String $key - Blowfish key
	 * @return String
	 */
	static function blowfishDecrypt($str, $key)
	{
		require_once dirname(__FILE__) .'/blowfish/blowfish.class.php';

		$Blowfish = new Blowfish($key);
		return $Blowfish->Decrypt($str);
	}
}

?>