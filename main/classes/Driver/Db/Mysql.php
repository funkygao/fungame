<?php

namespace Driver\Db;

/**
 * <pre>
 *  Mysql
 *    |
 *  MysqlConnection
 *    |
 *  ShardConnectionFactory
 *    |
 *  ShardConnectionMaster
 * </pre>
 */
class Mysql
    implements Driver {

    public function query($pool, $table, $hintId, $sql, array $args = array(),
                          $cacheKey = '') {
        return $this->_getMysqlConn($pool, $hintId)->query($sql, $args);
    }

    public function queryShards($pool, $table, $sql, array $args = array()) {
        throw new \NotImplementedException(); // and will never implement this
    }

    private function _getMysqlConn($pool, $hintId) {
        static $lookupCaches = array();
        $cacheKey = "{$pool}:{$hintId}";
        if (isset($lookupCaches[$cacheKey])) {
            return $lookupCaches[$cacheKey];
        }

        list($_, $needLookupShard, $lookupTable) = ShardInfo::pool2shard($pool);
        if (!$needLookupShard) {
            $lookupCaches[$cacheKey] = Mysql\MysqlConnection::factory($pool);
        } else {
            $lookupRow = Mysql\ShardLookup::factory($lookupTable)->mapLookup($hintId);
            $lookupCaches[$cacheKey] = Mysql\MysqlConnection::factory($pool, $lookupRow['shardId']);
        }

        return $lookupCaches[$cacheKey];
    }

    public function close() {
        Mysql\MysqlConnection::cleanUp();
    }

    public function commitAll() {
        Mysql\MysqlConnection::commitAll();
    }

    public function rollbackAll() {
        Mysql\MysqlConnection::rollbackAll();
    }
}
