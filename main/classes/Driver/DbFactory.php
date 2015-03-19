<?php

namespace Driver;

final class DbFactory
    implements \Consts\LoggerConst, Db\Driver {

    /**
     * @var \Driver\Db\Driver
     */
    private $_driver;

    /**
     * @param string $driverType
     * @return Db\Driver
     */
    public static function instance($driverType = 'fae') {
        static $instance = NULL;
        if (NULL === $instance) {
            $instance = new self($driverType);
        }

        return $instance;
	}

    /**
     * @param string $driverType
     */
    private function __construct($driverType) {
		$driverClass = '\\Driver\\Db\\' . ucfirst($driverType);
		$this->_driver = new $driverClass();
	}

    /**
     * @param string $pool
     * @param string $table
     * @param int $hintId 0 if table is not sharded
     * @param string $sql
     * @param array $args
     * @param string $cacheKey
     * @return Db\DbResult
     */
    public function query($pool, $table, $hintId, $sql, array $args = array(),
                          $cacheKey = '') {
        \System\Logger::getLogger(__CLASS__)->debug(self::CATEGORY_DBQUERY,
            array(
                'op' => __FUNCTION__,
                'table' => "$pool:$table:$hintId",
                'sql' => $sql,
                'args' => $args,
            ));

        return $this->_driver->query($pool, $table, $hintId, $sql, $args, $cacheKey);
    }

    public function queryShards($pool, $table, $sql, array $args = array()) {
        \System\Logger::getLogger(__CLASS__)->debug(self::CATEGORY_DBQUERY,
            array(
                'op' => __FUNCTION__,
                'table' => $table,
                'sql' => $sql,
                'args' => $args,
            ));

        return $this->_driver->queryShards($pool, $table, $sql, $args);
    }

    public function close() {
        $this->_driver->close();
    }

    public function commitAll() {
        $this->_driver->commitAll();
    }

    public function rollbackAll() {
        $this->_driver->rollbackAll();
    }

}
