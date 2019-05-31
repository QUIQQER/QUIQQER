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
