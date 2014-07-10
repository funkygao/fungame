<?php

namespace Driver;

final class DbFactory implements \Consts\DbConst, \Consts\LoggerConst, Db\Driver {

	private static $_instances = array();

    /**
     * @var string
     */
    private $_pool;

    /**
     * @var \Driver\Db\Driver
     */
    private $_driver;

    /**
     * @param string $pool DB pool name
     * @param string $driverType
     * @return DbFactory
     */
    public static function instance($pool, $driverType = 'mysql') {
		if (!isset(self::$_instances[$pool])) {
			self::$_instances[$pool] = new self($pool, $driverType);
		}
		return self::$_instances[$pool];
	}

    /**
     * @param string $pool
     * @param string $driverType
     */
    private function __construct($pool, $driverType) {
        $this->_pool = $pool;
		$driverClass = '\\Driver\\Db\\' . ucfirst($driverType);
		$this->_driver = new $driverClass($this->_pool);
	}

    public static function userPool($driverType = 'mysql') {
        return self::instance(self::POOL_USER, $driverType);
    }

    public static function worldPool($driverType = 'mysql') {
        return self::instance(self::POOL_WORLD, $driverType);
    }

    public static function alliancePool($driverType = 'mysql') {
        return self::instance(self::POOL_ALLIANCE, $driverType);
    }

    public static function globalPool($driverType = 'mysql') {
        return self::instance(self::POOL_GLOBAL, $driverType);
    }

    public static function lookupPool($driverType = 'mysql') {
        return self::instance(self::POOL_LOOKUP, $driverType);
    }

    public static function toolsPool($driverType = 'mysql') {
        return self::instance(self::POOL_TOOLS, $driverType);
    }

    public static function ticketsPool($driverType = 'mysql') {
        return self::instance(self::POOL_TICKETS, $driverType);
    }

    public static function chatPool($driverType = 'mysql') {
        return self::instance(self::POOL_CHAT, $driverType);
    }

    /**
     * @param string $table
     * @param int $hintId
     * @param string $sql
     * @param array $args
     * @return DbResult
     */
    public function query($table, $hintId, $sql, array $args = array()) {
        \System\Logger::getLogger(__CLASS__)->debug(self::CATEGORY_DBQUERY,
            array(
                'call' => \System\RequestHandler::getInstance()->actionName(),
                'sql' => $sql,
                'args' => $args,
            ));

        return $this->_driver->query($table, $hintId, $sql, $args);
    }

    public function close() {
        $this->_driver->close();
    }

    public static function closeAll() {
        foreach (self::$_instances as $pool => $theFactory) {
            $theFactory->close();
        }
    }
}
