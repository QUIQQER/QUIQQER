<?php

/**
 * This file contains Utils_Array
 */

/**
 * Helper for array handling
 *
 * @author www.pcsg.de (Henning Leutz
 * @package com.pcsg.qui.utils
 */

class Utils_Array
{
	/**
	 * Checks if the array is associative
	 *
	 * @param array $array
	 * @return Bool
	 */
	static function isAssoc(array $array)
	{
		foreach ($array as $key => $value)
		{
			if (is_int($key)) {
				return false;
			}
		}

		return true;
	}

    /**
     * Converts an index array in an associative array
     *
     * @param array $array
     * @return array
     */
	static function toAssoc(array $array)
	{
	    $result = array();

	    for ($i = 0, $len = count($array); $i < $len; $i++) {
            $result[ $array[$i] ] = true;
	    }

	    return $result;
	}

	/**
	 * Converts an object to an array
	 *
	 * @param Object $obj
	 * @return Array
	 */
	static function objectToArray($obj)
	{
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		$arr  = array();

		if (!is_array($_arr)) {
			return $arr;
		}

		foreach ($_arr as $key => $val)
		{
			$val = (is_array($val) || is_object($val)) ? self::objectToArray($val) : $val;
			$arr[$key] = $val;
		}

		return $arr;
	}

	/**
	 * Converts an array to an object
	 *
	 * @param array $array
	 * @return Object
	 */
	static function arrayToObject($array=array())
	{
		if (empty($array)) {
			return false;
		}

		$data = false;

		foreach ($array as $akey => $aval) {
			$data->{$akey} = $aval;
		}

		return $data;
	}
}

?>