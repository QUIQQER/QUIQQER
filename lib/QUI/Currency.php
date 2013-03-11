<?php

/**
 * This file contains QUI_Currency
 */

/**
 * Currency class
 * Conversion and currency sign
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Currency
{
    static function Table()
    {
        return QUI_DB_PRFX . TABLE;
    }

    /**
     * Calculation
     *
     * @param float $amount
     * @param String $currency_from
     * @param String $currency_to
     *
     * @return float
     * @example calc(1.45, 'USD', 'EUR')
     */
    static function calc($amount, $currency_from, $currency_to='EUR')
    {
        if ( $currency_from === 'EUR' && $currency_to === 'EUR' ) {
            return $amount;
        }

        $signs = self::allCurrencies();

        if ( !isset( $signs[ $currency_from ] ) && $currency_from !== 'EUR' ) {
            throw new QException( 'Unknown currency: '. $currency_from );
        }

        if (!isset($signs[$currency_to]) && $currency_to !== 'EUR') {
            throw new QException( 'Unknown currency: '. $currency_to );
        }

        $rate_from_to_euro = self::getRate( $currency_from );

        // nach euro
        if ( $currency_to === 'EUR' ) {
            return $amount * (1 / $rate_from_to_euro);
        }

        if ( $currency_from === 'EUR' ) {
            return $amount * self::getRate( $currency_to );
        }

        $eur = self::calc( $amount, $currency_from );

        return $eur * self::getRate( $currency_to );
    }

    /**
     * Calculation width Sign
     *
     * @param float $amount
     * @param String $currency_from
     * @param String $currency_to
     *
     * @return String
     */
    static function calcWithSign($amount, $currency_from, $currency_to='EUR')
    {
        $amount = self::calc( $amount, $currency_from, $currency_to );
        $sign   = self::getSign( $currency_to );

        return  $amount .' '. $sign;
    }

    /**
     * get the exchange rate
     *
     * @param String $currency
     * @return float || false
     */
    static function getRate($currency)
    {
        $result = QUI::getDB()->select(array(
            'from' => self::Table(),
            'where' => array(
            	'currency' => $currency
            )
        ));

        if ( isset( $result[0] ) ) {
            return (float) $result[0]['rate'];
        }

        return false;
    }

    /**
     * return the currency sign data (text and sign)
     *
     * @param unknown_type $currency
     * @return Array
     */
    static function getSignData($currency)
    {
        $signs = self::allCurrencies();

        if ( isset( $signs[ $currency ] ) ) {
            return $signs[ $currency ];
        }

        return array();
    }

    /**
     * return the currency sign
     *
     * @param String $currency (EUR or USD or JPY ...)
     * @return String
     *
     * @example
     * echo QUI_Currency::getSign('EUR');
     */
    static function getSign($currency)
    {
        $signs = self::allCurrencies();

        if ( !isset( $signs[ $currency ] ) ) {
            return $currency;
        }

        if ( empty( $signs[ $currency ][ 'sign' ] ) ) {
            return $currency;
        }

        return $signs[ $currency ][ 'sign' ];
    }

    /**
     * Get all currency entries
     *
     * @return Array
     */
    static function allCurrencies()
    {
        return array(
        	'EUR' => array(
            	'text' => 'Euro',
                'sign' => '&euro;'
            ),
        	'USD' => array(
            	'text' => 'US dollar',
                'sign' => '$'
            ),
            'JPY' => array(
            	'text' => 'Japanese yen',
                'sign' => '&yen;'
            ),
            'BGN' => array(
            	'text' => 'Bulgarian lev',
                'sign' => 'лв'
            ),
            'CZK' => array(
            	'text' => 'Czech koruna',
                'sign' => 'Kč'
            ),
            'DKK' => array(
            	'text' => 'Danish krone',
                'sign' => 'kr'
            ),
            'GBP' => array(
            	'text' => 'Pound sterling',
                'sign' => '&pound;'
            ),
            'HUF' => array(
            	'text' => 'Hungarian forint',
                'sign' => ''
            ),
            'LTL' => array(
            	'text' => 'Lithuanian litas',
                'sign' => 'Lt'
            ),
            'LVL' => array(
            	'text' => 'Latvian lats',
                'sign' => 'Ls'
            ),
            'PLN' => array(
            	'text' => 'Polish zloty',
                'sign' => 'zł'
            ),
            'RON' => array(
            	'text' => 'New Romanian',
                'sign' => 'RON'
            ),
            'SEK' => array(
            	'text' => 'Swedish krona',
                'sign' => 'kr'
            ),
            'CHF' => array(
            	'text' => 'Swiss franc',
                'sign' => ''
            ),
            'NOK' => array(
            	'text' => 'Norwegian krone',
                'sign' => ''
            ),
            'HRK' => array(
            	'text' => 'Croatian kuna',
                'sign' => 'kn'
            ),
            'RUB' => array(
            	'text' => 'Russian rouble',
                'sign' => 'руб'
            ),
            'TRY' => array(
            	'text' => 'Turkish lira',
                'sign' => '₤'
            ),
            'AUD' => array(
            	'text' => 'Australian dollar',
                'sign' => 'A$'
            ),
            'BRL' => array(
            	'text' => 'Brasilian real',
                'sign' => ''
            ),
            'CAD' => array(
            	'text' => 'Canadian dollar',
                'sign' => 'C$'
            ),
            'CNY' => array(
            	'text' => 'Chinese yuan renminbi',
                'sign' => ''
            ),
            'HKD' => array(
            	'text' => 'Hong Kong dollar',
                'sign' => 'HK$'
            ),
            'IDR' => array(
            	'text' => 'Indonesian rupiah',
                'sign' => ''
            ),
            'ILS' => array(
            	'text' => 'Israeli shekel',
                'sign' => ''
            ),
            'INR' => array(
            	'text' => 'Indian rupee',
                'sign' => ''
            ),
            'KRW' => array(
            	'text' => 'South Korean won',
                'sign' => ''
            ),
            'MXN' => array(
            	'text' => 'Mexican peso',
                'sign' => ''
            ),
            'MYR' => array(
            	'text' => 'Malaysian ringgit',
                'sign' => ''
            ),
            'NZD' => array(
            	'text' => 'New Zealand dollar',
                'sign' => 'NZ$'
            ),
            'PHP' => array(
            	'text' => 'Philippine peso',
                'sign' => ''
            ),
            'SGD' => array(
            	'text' => 'Singapore dollar',
                'sign' => 'S$'
            ),
            'THB' => array(
            	'text' => 'Thai baht',
                'sign' => ''
            ),
            'ZAR' => array(
            	'text' => 'South African rand',
                'sign' => ''
            ),
            'ISK' => array(
            	'text' => 'Icelandic krona',
                'sign' => ''
            )
        );
    }

    /**
     * Currency Setup
     *
     * @ignore
     */
    static function setup()
    {
        QUI::getDB()->createTableFields(
            self::Table(),
            array(
                'currency' => 'varchar(5) COLLATE utf8_unicode_ci NOT NULL',
                'rate'     => 'DOUBLE NULL DEFAULT NULL'
    		)
		);

		QUI::getDB()->setIndex( self::Table(), 'currency' );

		// import
		self::import();
    }

    /**
     * Import an XML File
     * eg: http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml
     *
     * @param String $xmlfile - Path to XML File
     */
    static function import($xmlfile='http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml')
    {
	    $Dom = new DOMDocument();
        $Dom->load( $xmlfile );

        $list = $Dom->getElementsByTagName( 'Cube' );

        if ( !$list->length ) {
            return;
        }

        $values = array(
            'EUR' => '1.0'
        );

        for ( $c = 0; $c < $list->length; $c++ )
        {
            $Cube = $list->item( $c );

            $currency = $Cube->getAttribute( 'currency' );
            $rate     = $Cube->getAttribute( 'rate' );

            if ( empty( $currency ) ) {
                continue;
            }

            $values[ $currency ] = $rate;
        }

        $DataBase = QUI::getDB();

        foreach ( $values as $currency => $rate )
        {
            $result = $DataBase->select(array(
                'from'  => self::Table(),
                'where' => array(
                    'currency' => $currency
                )
            ));

            // Update
            if ( isset( $result[0] ) )
            {
                $DataBase->updateData(
                    self::Table(),
                    array( 'rate' => $rate ),
                    array( 'currency' => $currency )
                );
            } else
            {
                $DataBase->addData(
                    self::Table(),
                    array(
                    	'rate'     => $rate,
                    	'currency' => $currency
                    )
                );
            }
        }
    }
}

?>