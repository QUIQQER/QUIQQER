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

if (!function_exists("array_merge_recursive_overwrite")) {
    function array_merge_recursive_overwrite(array ...$arrays): array
    {
        $merged = [];
        foreach ($arrays as $current) {
            foreach ($current as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                        $merged[$key] = (__FUNCTION__)($merged[$key], $value);
                    } else {
                        $merged[$key] = $value;
                    }
                } else {
                    $merged[] = $value;
                }
            }
        }

        return $merged;
    }
}