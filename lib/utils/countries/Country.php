<?php

/**
 * This file contains the Utils_Countries_Country
 */

/**
 * A Country
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.countries
 */
class Utils_Countries_Country extends QDOM
{
	/**
	 * construcor
	 * If you want a country, use the manager
	 *
	 * @example
	 * $Country = Utils_Countries_Manager::get('de');
	 * $Country->getName()
	 *
	 * @param Array $params
	 */
	public function __construct($params)
	{
		if (!isset($params['countries_iso_code_2'])) {
			throw new QException('Parameter countries_iso_code_2 fehlt');
		}

		if (!isset($params['countries_iso_code_3'])) {
			throw new QException('Parameter countries_iso_code_3 fehlt');
		}

		if (!isset($params['countries_name'])) {
			throw new QException('Parameter countries_name fehlt');
		}

		if (!isset($params['countries_id'])) {
			throw new QException('Parameter countries_id fehlt');
		}

		parent::setAttributes($params);
	}

	/**
	 * Return the country code
	 * iso_code_2 or iso_code_2
	 *
	 * @param String $type
	 * @return String
	 */
	public function getCode($type='countries_iso_code_2')
	{
		switch ($type)
		{
			default:
			case 'countries_iso_code_2':
				return $this->getAttribute('countries_iso_code_2');
			break;

			case 'countries_iso_code_3':
				return $this->getAttribute('countries_iso_code_3');
			break;
		}
	}

	/**
	 * Return the name of the country
	 * observed System_Locale
	 *
	 * @return String
	 */
	public function getName()
	{
	    if ($this->existsAttribute( QUI::getLocale()->getCurrent() )) {
            return $this->getAttribute( QUI::getLocale()->getCurrent() );
	    }

		return $this->getAttribute('countries_name');
	}
}

?>