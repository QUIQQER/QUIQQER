<?php

/**
 * This file contains QUI_Licence
 */

/**
 * Licence class
 * Checks if a license is available and valid
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Licence
{
    /**
     * Internal licence
     * @var QConfig
     */
	private $_config;

	/**
	 * Konstrukt
	 * Prüft ob Hauptlizenz vorhanden ist
	 *
	 * @return Bool
	 */
	public function __construct()
	{
		$licence_file = CMS_DIR .'etc/licence.pcsg';

		if (!file_exists($licence_file))
		{
			throw new QException('Keine Lizenz');
			return false;
		}

		$config = QUI::getConfig('etc/licence.pcsg');
		$this->_config = $config->toArray();

		return true;
	}

	/**
	 * returns a licence attribute
	 *
	 * @param String $name
	 * @return String
	 */
	public function getAttribute($name)
	{
		if (isset($this->_config[$name])) {
			return $this->_config[$name];
		}

		return false;
	}

	/**
	 * Decrypt the license code via the update server
	 *
	 * @param String $code
	 * @return String
	 */
	static function decode($code)
	{
		$Curl = curl_init();

		curl_setopt($Curl, CURLOPT_URL, 'http://update.pcsg.de/server/licence.php');
		curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

		// ssl
		curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($Curl, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($Curl, CURLOPT_POST, 1);
        curl_setopt($Curl, CURLOPT_POSTFIELDS, array('code' => $code));

        $res = curl_exec($Curl);

		if (empty($res)) {
			throw new QException('The license code is invalid', $http_code);
		}

	    $http_code = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
		curl_close($Curl);

		if ($http_code == 404)
		{
			throw new QException(
				'The connection to the update server could not be established',
			    $http_code
			);
		}

		$res = json_decode($res, true);

		if (isset($res['QException'])) {
			throw new QException($res['QException']['message']);
		}

		return $res;
	}
}

?>