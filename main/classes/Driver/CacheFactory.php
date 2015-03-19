<?php

namespace Driver;

final class CacheFactory implements Cache\Driver, \Consts\ErrnoConst {

	protected static $instances = array();

    /**
     * @var array
     */
    protected $_config;

    /**
     * @var \Driver\Cache\Driver
     */
    protected $_driver;

    /**
     * @param string $pool pool name of cache servers
     * @param string $driver
     * @return CacheFactory
     */
    public static function instance($pool = 'default', $driver = 'fae') {
		if (!isset(self::$instances[$pool])) {
			self::$instances[$pool] = new self($pool, $driver);
		}
		return self::$instances[$pool];
	}

    /**
     * @param string $pool
     * @param string $driver
     * @throws \ExpectedErrorException
     */
    protected function __construct($pool, $driver) {
        if(($config = \System\Config::get('cache', $pool, NULL)) === NULL) {
            throw new \ExpectedErrorException('cache.undefined_pool ' . $pool, self::ERRNO_SYS_INVALID_ARGUMENT);
        }
        $config['pool'] = $pool;

		$this->_config = $config;
		$driverClass = '\\Driver\\Cache\\' . ucfirst($driver);
		$this->_driver = new $driverClass($this->_config);
	}

	public function get($key) {
		$key = $this->_sanitize($key);
		return $this->_driver->get($key);
	}

	public function set($key, $value, $expiration = NULL) {
		$key = $this->_sanitize($key);
		return $this->_driver->set($key, $value, $expiration);
	}

	public function add($key, $value, $expiration = NULL) {
		$key = $this->_sanitize($key);
		return $this->_driver->add($key, $value, $expiration);
	}

	public function delete($key) {
		$key = $this->_sanitize($key);
		return $this->_driver->delete($key);
	}

	public function increment($key, $delta = 1, $expiration = NULL) {
		$delta = (int)$delta;
		$key = $this->_sanitize($key);
		if (!$ret = $this->_driver->increment($key, $delta)) {
			if ($this->_driver->add($key, 0, $expiration)) {
				$ret = $this->_driver->increment($key, $delta);
			}
		}
		return $ret;
	}

	protected function _sanitize($key) {
		// Change slashes and spaces to underscores
		$newKey = str_replace(array('/' , '\\' , ' '), '_', $key);
		if ($newKey !== $key) {
            throw new \ExpectedErrorException("Invalid cache key: $key", self::ERRNO_SYS_INVALID_ARGUMENT);
		}
		return $newKey;
	}

}
