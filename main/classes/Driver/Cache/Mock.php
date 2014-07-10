<?php

namespace Driver\Cache;

class Mock implements Driver
{
    public function add($key, $value, $expiration)
    {
        return false;
    }

    public function set($key, $value, $expiration)
    {
        return false;
    }

    public function get($key)
    {
        return false;
    }

    public function delete($key)
    {
        return false;
    }

    public function increment($key, $delta)
    {
        return 0;
    }

}
