<?php

/**
 * This file contains the Utils_Countries_Manager
 */

/**
 * Country Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.countries
 */

class Utils_Countries_Manager extends QDOM
{
    /**
     * Return the real table name
     *
     * @return String
     */
    static function Table()
    {
        return QUI_DB_PRFX .'countries';
    }

    /**
     * Country setup
     */
    static function setup()
    {
        // Countries
		$db_countries = __DIR__ .'/countries.sql';
		$PDO          = QUI::getDataBase()->getPDO();

		if ( !file_exists( $db_countries ) ) {
		    return;
		}

		$sql = file_get_contents( $db_countries );
		$sql = str_replace( '{$TABLE}', self::Table(), $sql );
		$sql = explode( ';', $sql );

		foreach ( $sql as $query )
		{
			$query = trim( $query );

			if ( empty( $query ) ) {
				continue;
			}

			$PDO->exec( $query );
		}
    }

	/**
	 * Get a country
	 *
	 * @param String $code - the country code
	 * @return Utils_Countries_Country
	 *
	 * @example
	 * $Country = Utils_Countries_Manager::get('de');
	 * $Country->getName()
	 */
	static function get($code)
	{
	    $result = QUI::getDB()->select(array(
			'from'  => self::Table(),
			'where' => array(
				'countries_iso_code_2' => Utils_String::toUpper(
                    $code
                )
		  	),
		  	'limit' => '1'

		));

		if ( !isset( $result[0] ) ) {
			throw new QException( 'Das Land wurde nicht gefunden', 404 );
		}

		return new Utils_Countries_Country( $result[0] );
	}

	/**
	 * Get the complete country list
	 *
	 * @return Array
	 */
	static function getList()
	{
		$order = 'countries_name ASC';

		if ( QUI::getLocale()->getCurrent() === 'en' ) {
            $order = 'en ASC';
		}

		$result = QUI::getDB()->select(array(
			'from'  => self::Table(),
			'order' => $order
		));

		$countries = array();

		foreach ( $result as $entry ) {
			$countries[] = new Utils_Countries_Country( $entry );
		}

		return $countries;
	}
}

?>