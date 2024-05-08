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
    function array_key_first(array $arr): int|string|null
    {
        foreach (array_keys($arr) as $key) {
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
     * @return int|string|null
     */
    function array_key_last($array): int|string|null
    {
        if (!is_array($array) || empty($array)) {
            return null;
        }

        return array_keys($array)[count($array) - 1];
    }
}

if (!function_exists("array_merge_recursive_overwrite")) {
    /**
     * Merge one or more arrays recursively
     *
     * Merges the elements of one or more arrays together so that the values of one are appended to the end of the previous one.
     *
     * If the input arrays have the same string keys, then the latter value for that key will overwrite the previous one,
     * and this is done recursively, so that if one of the values is an array itself, the function will merge it with a corresponding entry in another array too.
     * If, however, the arrays have the same numeric key, the latter value will not overwrite the original value, but will be appended.
     *
     * Mimics the behaviour of array_merge_recursive() with the exception that duplicate string keys are overwritten instead of merged into an array,
     * more akin to array_merge().
     *
     * @see https://www.php.net/manual/en/function.array-merge-recursive.php
     * @see https://www.php.net/manual/en/function.array-merge.php
     *
     * @param array[] $arrays Variable list of arrays to recursively merge.
     *
     * @return array The merged array
     */
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
