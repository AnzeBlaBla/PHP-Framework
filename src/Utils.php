<?php

namespace AnzeBlaBla\Framework;

class Utils
{
    public static function get_os()
    {
        return strtoupper(substr(PHP_OS, 0, 3));
    }

    public static function fix_path($path)
    {
        // if windows, make all slashes backslashes, otherwise make all slashes forward slashes
        if (Utils::get_os() == 'WIN') {
            $path = str_replace('/', '\\', $path);
        } else {
            $path = str_replace('\\', '/', $path);
        }

        // remove double slashes
        $path = str_replace('//', '/', $path);
        $path = str_replace('\\\\', '\\', $path);

        return realpath($path);
    }


    public static function debug_print($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}
