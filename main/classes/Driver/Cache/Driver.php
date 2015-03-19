<?php

namespace Driver\Cache;

/**
 * <pre>
 *
 *              Driver
 *                 |
 *  +--------------------------------+
 *  |      |           |        |    |    
 * Fae  Memcache  Memcached    Apc  Redis
 *
 * </pre>
 */
interface Driver
{
    /**
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     * @return bool False if the key already exists
     */
    public function add($key, $value, $expiration);

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     * @return bool Success or fail
     */
    public function set($key, $value, $expiration);

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key);

    /**
     * @param string $key
     * @param int $delta If negative, means decrement
     * @return int
     */
    public function increment($key, $delta);
}
