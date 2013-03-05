<?php

/**
 * This file contains the Utils_Security_DigestMD5
 */

/**
 * Digest MD5
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.security
 *
 * @todo Check if it works, at the moment it is never used or search for alternatives
 */

class Utils_Security_DigestMD5 extends QDOM
{
	/**
	 * Erstellt den MD5-Digest Response
	 *
	 * @return String
	 */
	public function getRespons()
	{
		$decoded = base64_decode( $this->getAttribute('b64str') );
        $decoded = $this->_explode($decoded);

	 	if (isset($decoded['digest-uri'])) {
			$decoded['digest-uri'] = $this->getAttribute('begin_uri').$decoded['digest-uri'];
        }

        $decoded['cnonce'] = $this->_getCnonce();

	 	if (isset($decoded['qop']) &&
	 		strpos($decoded['qop'], 'auth') !== false)
	 	{
			$decoded['qop'] = 'auth';
		}

		$response = array(
			'username' => $this->getAttribute('user'),
			'response' => $this->_encryptPassword(
				array_merge($decoded, array('nc' => '00000001'))
			),
			'charset'	=> 'utf-8',
			'nc' 		=> '00000001',
			'qop' 		=> 'auth',
		);

		// Response Prüfung
		$needle = array('nonce', 'digest-uri', 'realm', 'cnonce');

		foreach ($needle as $key)
		{
			if (isset($decoded[$key])) {
				$response[$key] = $decoded[$key];
			}
		}

        return base64_encode( $this->_implode($response) );
	}

	/**
	 * Versclüsselt die Daten
	 *
	 * @param Array $data
	 *
	 * @return String
	 */
	protected function _encryptPassword($data)
	{
		if (!isset($data['realm'])) {
			$data['realm'] = '';
		}

		if (!isset($data['cnonce'])) {
			$data['cnonce'] = '';
		}

		if (!isset($data['digest-uri'])) {
			$data['digest-uri'] = '';
		}


		$pack = md5($this->getAttribute('user') .':'. $data['realm'] .':'. $this->getAttribute('password'));

		if (isset($data['authzid']))
		{
			$a1 = pack('H32',$pack) . sprintf(
				':%s:%s:%s', $data['nonce'], $data['cnonce'], $data['authzid']
			);
		} else
		{
			$a1 = pack('H32',$pack) . sprintf(
				':%s:%s', $data['nonce'], $data['cnonce']
			);
		}

		$a2 = 'AUTHENTICATE:'. $data['digest-uri'];

		return md5(sprintf('%s:%s:%s:%s:%s:%s', md5($a1),
			$data['nonce'], $data['nc'], $data['cnonce'], $data['qop'],
			md5($a2)
		));
	}

	/**
	 * Gibt eine CNonce zurück
	 *
	 * @return unknown
	 */
	protected function _getCnonce()
	{
		if (file_exists('/dev/urandom')) {
			return base64_encode( fread(fopen('/dev/urandom', 'r'), 32) );
		}

		if (file_exists('/dev/random'))	{
			return base64_encode(fread(fopen('/dev/random', 'r'), 32));
		}

		$str = '';
		mt_srand( (double)microtime()*10000000 );

		for ($i = 0; $i < 32; $i++) {
			$str .= chr(mt_rand(0, 255));
		}

		return base64_encode($str);
    }

	/**
	 * Explode Data Pairs
	 *
	 * @param String $data
     */
	protected function _explode($data)
	{
		$data  = explode(',', $data);
		$pairs = array();
		$key   = false;

		foreach ($data as $pair)
		{
			$dd = strpos($pair, '=');

			if ($dd)
			{
				$key         = trim(substr($pair, 0, $dd));
				$pairs[$key] = trim(trim(substr($pair, $dd + 1)), '"');
			} else if (strpos(strrev(trim($pair)), '"') === 0 && $key)
			{
				$pairs[$key] .= ',' . trim(trim($pair), '"');
				continue;
			}
		}

		return $pairs;
	}

	/**
	 * Implode Data für den Response
	 *
	 * @param unknown_type $data
	 */
	protected function _implode($data)
	{
		$return = array();

		foreach ($data as $key => $value) {
			$return[] = $key .'="'. $value .'"';
		}

		return implode(',', $return);
	}

	/**
	 * Verschlüsselt ein Passwort
	 *
	 * @param unknown_type $pass
	 * @param unknown_type $switch
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
	 * Entschlüsselt ein Passwort
	 *
	 * @param unknown_type $pass
	 * @param unknown_type $switch
	 *
	 * @return String
	 */
	static function decrypt($pass, $switch=3)
	{
		$newpass = base64_decode($pass);
		return substr($pass, -3) . substr($pass, 0, -3);
	}
}

?>