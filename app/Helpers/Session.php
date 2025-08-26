<?php

namespace App\Helpers;

class Session
{
    /**
     * Get a value from the session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return session($key, $default);
    }
}
