<?php

if (!function_exists('pr')) {
    function pr($array)
    {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }
}
if (!function_exists('dd')) {
    function dd($array)
    {
        echo "<pre>";
        var_dump($array);
        echo "</pre>";
        exit;
    }
}