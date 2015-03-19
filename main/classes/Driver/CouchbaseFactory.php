<?php

namespace Driver;

// Currently only support fae driver although php-couchbase ext exists.
final class CouchbaseFactory {

    const DEFAULT_BUCKET = 'default';

    /**
     * @var \fun\rpc\FunServantClient
     */
    private $_fae;

    /**
     * @var \fun\rpc\Context
     */
    private $_ctx;

    /**
     * @return CouchbaseFactory
     */
    public static function instance() {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self();
            $instance->_fae = \FaeEngine::client();
            $instance->_ctx = \FaeEngine::ctx();
        }

        return $instance;
    }

    /**
     * Set a key with string value.
     *
     * Max value size is 25 MB
     * Max key size is 250B
     *
     * @param string $key
     * @param string $val
     * @param int $expire
     * @param string $bucket
     */
    public function set($key, $val, $expire = 0, $bucket = self::DEFAULT_BUCKET) {
        $this->_fae->cb_set(
            $this->_ctx,
            $bucket,
            $key,
            $val,
            $expire
        );
    }

    /**
     * Add a value to this bucket.
     *
     * Like set except that nothing happens if the key exists
     *
     * @param string $key
     * @param string $val
     * @param int $expire
     * @param string $bucket
     * @return bool False if the key already exists in this bucket
     */
    public function add($key, $val, $expire = 0, $bucket = self::DEFAULT_BUCKET) {
        return $this->_fae->cb_add(
            $this->_ctx,
            $bucket,
            $key,
            $val,
            $expire
        );
    }

    /**
     * @param string $key
     * @param string $bucket
     */
    public function del($key, $bucket = self::DEFAULT_BUCKET) {
        $this->_fae->cb_del(
            $this->_ctx,
            $bucket,
            $key
        );
    }

    /**
     * Append raw data to an existing item.
     *
     * @param string $key
     * @param string $val
     * @param string $bucket
     */
    public function append($key, $val, $bucket = self::DEFAULT_BUCKET) {
        $this->_fae->cb_append(
            $this->_ctx,
            $bucket,
            $key,
            $val
        );
    }

    /**
     * @param string $key
     * @param string $bucket
     * @return \fun\rpc\TCouchbaseData
     */
    public function get($key, $bucket = self::DEFAULT_BUCKET) {
        return $this->_fae->cb_get(
            $this->_ctx,
            $bucket,
            $key
        );
    }


    /**
     * Fetches multiple keys concurrently.
     *
     * @param array $keys List of string
     * @param string $bucket
     * @return array {key: val}
     */
    public function gets(array $keys, $bucket = self::DEFAULT_BUCKET) {
        return \FaeEngine::client()->cb_gets(
            $this->_ctx,
            $bucket,
            $keys
        );
    }

}
