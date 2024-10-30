<?php

namespace BuddyBot;

class Loader
{   
    public static function loadClass($class)
    {
        $file = (plugin_dir_path(__DIR__) . strtolower(str_replace('\\', '/', $class)) . '.php');
        $is_internal = strpos($file, 'buddybot');

        if ($is_internal !== false) {
            include($file);
        }
    }
}