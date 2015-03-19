<?php

namespace Driver\Db\Mysql;

final class ShardLookup
    implements \Consts\DbConst {

    private $table;

    private function __construct($table) {
        $this->table = $table;
    }

    /**
     * @param string $table
     * @return ShardLookup
     */
    public static function factory($table) {
        static $instances = array();
        if (!isset($instances[$table])) {
            $instances[$table] = new self($table);
        }

        return $instances[$table];
    }

    /**
     * @param int $entityId
     * @return array
     * @throws EntityDoesNotExistException
     * @throws \ShardLockException
     */
    public function mapLookup($entityId) {
        $dbResult = MysqlConnection::factory(self::POOL_LOOKUP, 0)
            ->query("SELECT * FROM  {$this->table} WHERE entityId = ?",
                array($entityId));
        if (!$dbResult->getResults()) {
            throw new EntityDoesNotExistException("Entity {$this->table}:$entityId does not exit.");
        }

        $row = $dbResult->getResults()[0];
        if ($row["shardLock"]) {
            throw new \ShardLockException("Entity {$this->table}:$entityId is locked");
        }

        return $row;
    }

}

class EntityDoesNotExistException extends \Exception {}
