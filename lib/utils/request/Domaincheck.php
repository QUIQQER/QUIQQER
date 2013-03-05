<?php

/**
 * This file contains Utils_Request_Domaincheck
 */

/**
 * Domainchecker
 *
 * Prüft Top Level Domains auf Verfügbarkeit
 * Folgende TLDS können geprüft werden:
 * 		de, com, net, edu, org, name, info,
 * 		biz, be, at, ag, am, as, cd, ch, cx,
 *      dk, it, li, nu, ru, uk.com, eu.com, ws
 *
 * @example
 * $Check = new Utils_Request_Domaincheck();
 * $Check->setAttribute('tld', 'de');
 * $Check->setAttribute('domain', 'pcsg');
 *
 * $Check->check();
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.request
 *
 * @copyright www.pcsg.de
 *
 * @todo look at namerobot classes, maybe the implementation is better, this class is old
 * @todo docu translation
 */

class Utils_Request_Domaincheck extends QDOM
{
	const DOMAIN_FREE    = 1;  // Domain noch frei
	const DOMAIN_FORGIVE = 0;  // Domain vergeben
	const DOMAIN_ILLEGAL = -1; // Anfrage unzulässig
	const DOMAIN_CERROR  = -2; // Verbindungsfehler zu einem TLD Server

	/**
	 * Konstruktor
	 *
	 * @param Array $settings
	 */
	public function __construct($settings=array())
	{
		// default
		$this->setAttribute('tld', 'de');

		foreach ($settings as $key => $value) {
			$this->setAttribute($key, $value);
		}
	}

	/**
	 * Führt eine Prüfung durch
	 *
	 * @return Integer DOMAIN_FREE | DOMAIN_FORGIVE | DOMAIN_ILLEGAL | DOMAIN_CERROR
	 * @throws QException
	 */
	public function check()
	{
		if (!$this->getAttribute('domain') ||
			$this->getAttribute('domain') == '')
		{
			throw new QException(
				'domain Attribute fehlt. Es wurde kein Domainnamen zur Prüfung angegeben'
			);
		}

		$this->setAttribute('tld', str_replace(' ', '-', $this->getAttribute('tld')));

		switch ($this->getAttribute('tld'))
		{
			case 'de':
				$server = 'whois.denic.de';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'status: free') !== false) {
					return self::DOMAIN_FREE;
				}

				if (strpos($result, 'status: invalid') !== false) {
					return self::DOMAIN_ILLEGAL;
				}

				if (strpos($result, 'connection refused') !== false) {
					return self::DOMAIN_CERROR;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'com':
			case 'net':
			case 'edu':
				$server = 'whois.internic.net';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'org':
				$server = 'whois.networksolutions.com';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'name':
				$server = 'whois.nic.name';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'info':
				$server = 'whois.afilias.net';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'not found') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'biz':
				$server = 'whois.nic.biz';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'not found') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'at':
				$server = 'whois.nic.at';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'nothing found') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'be':
				$server = 'whois.dns.be';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'free') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;


			case 'ag':
				$server = 'whois.nic.ag';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'not found') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'am':
				$server = 'whois.nic.am';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'as':
				$server = 'whois.nic.as';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'domain not found') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'cd':
				$server = 'whois.cd';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'ch':
				$server = 'whois.nic.ch';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'not have an entry') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'cx':
				$server = 'whois.nic.cx';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'status: not registered') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'dk':
				$server = 'whois.dk-hostmaster.dk';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'No entries found') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'it':
				$server = 'whois.nic.it';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'Status: AVAILABLE') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'li':
				$server = 'whois.nic.li';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'do not have an entry') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'lu':
				$server = 'whois.dns.lu';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no such domain') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'nu':
				$server = 'whois.nic.nu';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match for') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'ru':
				$server = 'whois.ripn.net';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no entries found') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'uk.com':
				$server = 'whois.centralnic.com';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match for') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'eu.com':
				$server = 'whois.centralnic.com';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'no match') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			case 'ws':
				$server = 'whois.nic.ws';
				$domain = $this->getAttribute('domain') .'.'. $this->getAttribute('tld');

				$result = $this->_request($domain, $server);

				if (strpos($result, 'No match for') !== false) {
					return self::DOMAIN_FREE;
				}

				return self::DOMAIN_FORGIVE;
			break;

			default:
				throw new QException(
					'Keine oder unbekannte tld angegeben'
				);
			break;
		}
	}

	/**
	 * Anfrage an einen Domain Server stellen
	 *
	 * @param String $domain
	 * @param String $server
	 *
	 * @return String
	 */
	protected function _request($domain, $server)
	{
		$check = fsockopen($server, 43);

		if (!$check)
		{
			throw new QException(
				'Konnt kein Socket zu '. $domain .' aufbauen'
			);
		}

		$result = '';

		fputs($check, $domain." \r\n");

		while (!feof($check)) {
			$result = $result . fgets($check, 128);
		}

		fclose($check);


		$result = strtolower($result);
		$result = str_replace("\n", " ", $result);
		$result = preg_replace("/ +/", ' ', $result);

		return $result;
	}
}

?>