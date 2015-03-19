<?php

namespace Driver\Cache;

class Memcached implements Driver, \Consts\ErrnoConst
{
    private $_expiration;

    /**
     * @var \Memcached
     */
    private $_backend;

    public function __construct(array $config)
    {
        if (empty($config) || empty($config['servers'])) {
            throw new \ExpectedErrorException('Invalid config: ' . json_encode($config), self::ERRNO_SYS_INVALID_ARGUMENT);
        }

        if (!empty($config['expiration'])) {
            $this->_expiration = (int)$config['expiration'];
        } else {
            $this->_expiration = 86400; // a day
        }
        $this->_backend = new \Memcached();
        foreach ($config['servers'] as $server) {
            $this->_backend->addserver($server[0], $server[1]); // host, port
        }
    }

    public function add($key, $value, $expiration = NULL)
    {
        $expiration = $expiration === NULL ? $this->_expiration : $expiration;
        return $this->_backend->add($key, $value, $expiration);
    }

    public function set($key, $value, $expiration = NULL)
    {
        $expiration = $expiration === NULL ? $this->_expiration : $expiration;
        return $this->_backend->set($key, $value, $expiration);
    }

    public function get($key)
    {
        return $this->_backend->get($key);
    }

    public function delete($key)
    {
        return $this->_backend->delete($key);
    }

    public function increment($key, $delta)
    {
        return $this->_backend->increment($key, (int)$delta);
    }

}
