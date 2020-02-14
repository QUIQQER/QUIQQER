<?php

/**
 * This file contains important PHP polyfills
 */

if (!function_exists('array_key_first')) {
    /**
     * https://www.php.net/manual/de/function.array-key-first.php
     *
     * @param array $arr
     * @return int|string|null
     */
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }

        return null;
    }
}

if (!function_exists("array_key_last")) {
    /**
     * https://www.php.net/manual/de/function.array-key-last.php#123016
     *
     * @param $array
     * @return mixed|null
     */
    function array_key_last($array)
    {
        if (!\is_array($array) || empty($array)) {
            return null;
        }

        return \array_keys($array)[\count($array) - 1];
    }
}
