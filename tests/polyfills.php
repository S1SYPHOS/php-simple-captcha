<?php

if (!function_exists('str_contains')) {
    /**
     * Checks whether string contains substring
     *
     * See https://www.php.net/manual/en/function.str-contains.php#125977
     *
     * @param string $haystack String
     * @param string $needle Substring
     * @return bool
     */
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
