<?php

namespace Driver\Cache;

use fun\rpc\TMemcacheData,
    fun\rpc\TCacheMissed;

class Fae implements Driver {
    const MMC_SERIALIZED = 1;
    const MMC_COMPRESSED = 2;

    private $_pool;
    private $_expiration;

    public function __construct(array $config) {
        if (!isset($config['pool'])) {
            throw new \InvalidArgumentException('undefined cache pool');
        }

        $this->_pool = $config['pool'];
        if(isset($config['expiration'])) {
            $this->_expiration = $config['expiration'];
        } else {
            $this->_expiration = 86400;
        }
    }

    public function add($key, $data, $expiration) {
        $expiration = $expiration === NULL ? $this->_expiration : $expiration;
        return \FaeEngine::client()->mc_add(
            \FaeEngine::ctx(),
            $this->_pool,
            $key,
            $this->_marshal($data),
            $expiration
        );
    }

    public function set($key, $data, $expiration) {
        $expiration = $expiration === NULL ? $this->_expiration : $expiration;
        return \FaeEngine::client()->mc_set(
            \FaeEngine::ctx(),
            $this->_pool,
            $key,
            $this->_marshal($data),
            $expiration
        );
    }

    public function get($key) {
        try {
            $data = \FaeEngine::client()->mc_get(
                \FaeEngine::ctx(),
                $this->_pool,
                $key
            );
            return $this->_unmarshal($data);
        } catch (TCacheMissed $ex) {
            return NULL;
        }
    }

    public function increment($key, $delta) {
        return \FaeEngine::client()->mc_increment(
            \FaeEngine::ctx(),
            $this->_pool,
            $key,
            $delta
        );
    }

    public function delete($key) {
        return \FaeEngine::client()->mc_delete(
            \FaeEngine::ctx(),
            $this->_pool,
            $key
        );
    }

    private function _unmarshal(TMemcacheData $data) {
        if ($data->flags & self::MMC_COMPRESSED) {
            $data->data = gzuncompress($data->data);
        }
        if ($data->flags & self::MMC_SERIALIZED) {
            return unserialize($data->data);
        } else {
            return $data->data;
        }
    }

    private function _marshal($data) {
        $ret = new TMemcacheData();
        switch (gettype($data)) {
            case 'integer':
            case 'boolean':
            case 'double':
            case 'string':
            case 'float':
                $ret->data = $data;
                $ret->flags = 0;
                break;

            default:
                $ret->data = serialize($data);
                $ret->flags |= self::MMC_SERIALIZED;
                break;
        }

        return $ret;
    }

}
