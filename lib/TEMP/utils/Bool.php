<?php

/**
 * This file contains Utils_Bool
 */

/**
 * Helper for bool type handling
 *
 * @author www.pcsg.de (Henning Leutz
 * @package com.pcsg.qui.utils
 */

class Utils_Bool
{
    /**
     * internal var
     * @var String|Bool
     */
	public $_bool;

	/**
	 * constructor
	 *
	 * @param String|Bool $bool
	 */
	public function __construct($bool)
	{
		$this->_bool = (bool)$bool;
	}

	/**
	 * Converts JavaScript Boolean values ​​for PHP
	 *
	 * @param String|Bool $value
	 * @return Bool
	 */
	static function JSBool($value)
	{
 		if ( is_bool( $value ) ) {
			return $value;
		}

	    if ( is_integer( $value ) )
	    {
            if ( $value == 1 ) {
                return true;
            }

	        return false;
		}

		if ( $value == 'true' || $value == '1' ) {
			return true;
		}

		if ( $value == 'false' || $value == '0' ) {
			return false;
		}

		return $value;
	}
}

?>