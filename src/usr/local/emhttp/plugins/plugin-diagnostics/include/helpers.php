<?php

if ( ! function_exists('str_starts_with')) {
    function str_starts_with(string $str, string $start): bool
    {
        return (@substr_compare($str, $start, 0, strlen($start)) == 0);
    }
}
