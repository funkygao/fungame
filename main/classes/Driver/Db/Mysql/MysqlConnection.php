<?php

namespace Driver\Db\Mysql;

final class MysqlConnection implements \Consts\ErrnoConst {

    private $TRANSACTION_START = 0;

    private static $instances = array();

    private $shardInfo;

    /**
     * @param \Driver\Db\ShardInfo $shardInfo
     * @return \Driver\Db\Mysql\MysqlConnection
     */
    protected function __construct(\Driver\Db\ShardInfo $shardInfo) {
        $this->shardInfo = $shardInfo;
    }

    /**
     *
     * @param string $pool
     * @param int $shardId
     * @return MysqlConnection
     */
    public static function factory($pool, $shardId = 0) {
        $info = \Driver\Db\ShardInfo::factory($pool, $shardId);
        $key = $info->getSlug();
        if (!isset(self::$instances[$key])) {
            // a new db instance connection created
            self::$instances[$key] = new self($info);

            if (\System\RequestHandler::getInstance()->transactional) {
                \System\Logger::getLogger(__CLASS__)->debug('transaction', array(
                    'op' => 'begin',
                    'pool' => $pool,
                    'info' => $info->getSlug(),
                    'shardId' => $shardId,
                ));

                self::$instances[$key]->begin();
            }
        }

        return self::$instances[$key];
    }

    public static function commitAll() {
        if (!\System\RequestHandler::getInstance()->transactional) {
            return;
        }

        \System\Logger::getLogger(__CLASS__)->debug('transaction', array(
            'op' => 'commitAll',
        ));

        foreach (self::$instances as $conn) {
            $conn->commit();
        }
    }

    public static function rollbackAll() {
        if (!\System\RequestHandler::getInstance()->transactional) {
            return;
        }

        \System\Logger::getLogger(__CLASS__)->debug('transaction', array(
            'op' => 'rollbackAll',
        ));

        foreach (self::$instances as $conn) {
            $conn->rollback();
        }
    }

    private function _getConn() {
        return ShardConnectionFactory::factory($this->shardInfo);
    }

    /**
     *
     * @param string $sql
     * @param array $args
     * @throws \ExpectedErrorException
     * @return \Driver\Db\DbResult
     */
    public function query($sql, $args = array()) {
        if ($args && !is_array($args)) {
            throw new \ExpectedErrorException("Args if set must be an array holding the bind order", self::ERRNO_SYS_INVALID_ARGUMENT);
        }

        return $this->_getConn()->query($sql, $args);
    }

    public function begin() {
        $this->TRANSACTION_START++;
        $this->query('START TRANSACTION');
    }

    public function commit() {
        $this->TRANSACTION_START--;
        if ($this->TRANSACTION_START < 0) {
            throw new ShardTransactionException("Dude WTF a transaction was not started");
        }

        if ($this->TRANSACTION_START == 0) {
            $this->query('COMMIT');
        }
    }

    public function rollback() {
        $this->query("ROLLBACK");
    }

    /**
     * @param $level SERIALIZABLE|REPEATABLE READ|READ COMMITTED|READ UNCOMMITTED
     */
    public function setIsolationLevel($level) {
        $this->query("SET SESSION TRANSACTION ISOLATION LEVEL $level");
    }

    public static function cleanUp() {
        // TODO
    }

}

class ShardTransactionException extends \Exception { }

class ShardQueryException extends \Exception { }
