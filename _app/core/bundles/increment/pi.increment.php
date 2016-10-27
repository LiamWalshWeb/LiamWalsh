<?php

class Plugin_increment extends Plugin
{
    public static $container = array();

    public function __call($method, $arguments)
    {
        if ( ! isset(self::$container[$method])) {
            // Start incrementing at 1
            self::$container[$method] = 1;
        }

        // Iterate!
        return self::$container[$method]++;
    }
}