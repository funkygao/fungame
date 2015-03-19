<?php

namespace Driver\Cache;

final class Redis implements Driver, \Consts\ErrnoConst {
    /**
     * @var \Redis
     */
    private $_backend;

    public function __construct(array $config) {
        if (empty($config) || empty($config['servers'])) {
            throw new \ExpectedErrorException('Invalid config', self::ERRNO_SYS_INVALID_ARGUMENT);
        }
        $this->_backend = \Driver\RedisFactory::instance($config['pool']);
    }

    public function add($key, $value, $expiration = NULL) {
        $result = $this->_backend->setnx($key, json_encode($value));
        if ((1 == $result) && ($expiration != NULL)) {
            $this->_backend->expire($key, $expiration);
        }
        return 1 == $result ? TRUE : FALSE ;
    }

    public function set($key, $value, $expiration = NULL) {
        $ok = $this->_backend->set($key, json_encode($value));
        if ($ok && $expiration != NULL) {
            $this->_backend->expire($key, $expiration);
        }
        return $ok;
    }

    public function get($key)  {
        return json_decode($this->_backend->get($key), TRUE);
    }

    //redis 'del' operation returns the success deleted keys amount;convert it from int to bool
    public function delete($key) {
        $result = $this->_backend->del($key);
        return $result > 0 ? TRUE : FALSE;
    }

    public function increment($key, $delta) {
        return $this->_backend->incrBy($key, $delta);
    }

}
