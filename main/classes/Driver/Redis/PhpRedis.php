<?php

namespace Driver\Redis;

final class PhpRedis
    implements Driver {

    const
        CONF_RETRY_MS = 100, // 100ms delay between reconnection attempts
        CONF_TIMEOUT = 4; // in sec

    /**
     * @var \Redis[] Key is host:port, value is {@link \Redis}
     */
    private $_redisServers;

    /**
     * @var \Utils\Flexihash
     */
    private $_hasher;

    /**
     * @param string $pool
     */
    public function __construct($pool) {
        $this->_hasher = new \Utils\Flexihash();
        $config = \System\Config::get('redis', $pool, NULL);
        foreach ($config['servers'] as $server) {
            list($host, $port, $weight) = $server;
            $this->_hasher->addTarget("$host:$port", $weight);
        }
    }

    private function _getRedis($key) {
        $server = $this->_hasher->lookup($key);
        if (!isset($this->_redisServers[$server])) {
            $this->_redisServers[$server] = new \Redis();
            //$this->_redisServers[$server]->setOption(\Redis::OPT_SERIALIZER, \Redis::REDIS_SERIALIZER_PHP);
            list($host, $port) = explode(':', $server);
            if (!$this->_redisServers[$server]->connect($host, $port,
                self::CONF_TIMEOUT, NULL, self::CONF_RETRY_MS)) {
                // connect fail will throw \RedisException TODO
            }
        }

        return $this->_redisServers[$server];
    }

    public function __call($func, $args) {
        // $func may be 'multi' without any key
        // TODO in that case, how to route the key?
        $key = count($args) > 0 ? $args[0] : '';
        return call_user_func_array(array($this->_getRedis($key), $func), $args);
    }

}
