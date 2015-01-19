<?php

namespace Model\Base;

abstract class CouchbaseRecord {

    /**
     * @var \Driver\CouchbaseFactory
     */
    private static $_couchbase = NULL;

    private static $_cache = array(); // TODO

    /**
     * @var string
     */
    private $_key;

    /**
     * @var array
     */
    private $_value;

    public final function __construct($key, array $value) {
        if (self::$_couchbase === NULL) {
            self::$_couchbase = \Driver\CouchbaseFactory::instance();
        }

        $this->_key = $key;
        $this->_value = $value;
    }

    public final function __get($column) {
        return $this->_value[$column];
    }

    public final function __set($column, $value) {
        $this->_value[$column] = $value; // TODO dirty checking, Flusher register
    }

    public final function __isset($column) {
        return isset($this->_value[$column]);
    }

    /**
     * @param string $key
     * @return CouchbaseRecord
     */
    public static final function get($key) {
        if (self::$_couchbase === NULL) {
            self::$_couchbase = \Driver\CouchbaseFactory::instance();
        }

        $recordClass = get_called_class();
        $key = $recordClass . ':' . $key; // FIXME make key shorter
        $val = self::$_couchbase->get($key);
        return new $recordClass(json_decode($val, TRUE));
    }

    public final function save() {
        self::$_couchbase->set($this->_key, json_encode($this->_value));
    }

}
