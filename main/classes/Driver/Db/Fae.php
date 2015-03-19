<?php

namespace Driver\Db;

class Fae implements Driver {

    /**
     * CAUTION: if select, the 'SELECT' must be upper case.
     * string.toupper has too much overhead.
     *
     * @param string $pool e,g. 'user' instead of 'UserShard', ref DbConst
     * @param string $table
     * @param int $hintId
     * @param string $sql
     * @param array $args
     * @param string $cacheKey
     *
     * @return \Driver\Db\DbResult
     */
    public function query($pool, $table, $hintId, $sql, array $args = array(),
                          $cacheKey = '') {
        list($shardName, $_, $_) = ShardInfo::pool2shard($pool);
        $mysqlResult = \FaeEngine::client()->my_query(
            \FaeEngine::ctx(),
            $shardName, $table, $hintId,
            $sql, $args, $cacheKey
        );

        $result = new \Driver\Db\DbResult();
        $result->setAffectedRows($mysqlResult->rowsAffected);
        $result->setInsertId($mysqlResult->lastInsertId);
        if (!$mysqlResult->rowsAffected) {
            // SELECT
            if (!empty($mysqlResult->rows)) {
                $result->setResults($this->_fetch_assoc_rows($mysqlResult->cols,
                    $mysqlResult->rows));
            }
        }

        return $result;
    }

    public function queryShards($pool, $table, $sql, array $args = array()) {
        list($shardName, $_, $_) = ShardInfo::pool2shard($pool);
        $mysqlResult = \FaeEngine::client()->my_query_shards(
            \FaeEngine::ctx(),
            $shardName,
            $table,
            $sql,
            $args
        );

        $result = new \Driver\Db\DbResult();
        $result->setAffectedRows($mysqlResult->rowsAffected);
        $result->setInsertId($mysqlResult->lastInsertId);
        if (!$mysqlResult->rowsAffected) {
            // SELECT
            if (!empty($mysqlResult->rows)) {
                $result->setResults($this->_fetch_assoc_rows($mysqlResult->cols,
                    $mysqlResult->rows));
            }
        }

        return $result;
    }

    // TODO use array_flip($columns)
    private function _fetch_assoc_rows($columns, $rows) {
        $ret = array();
        foreach ($rows as $row) {
            $assocRow = array();
            $i = 0;
            foreach ($row as $val) {
                $assocRow[$columns[$i]] = $val;
                $i++;
            }

            $ret[] = $assocRow;
        }

        return $ret;
    }

    public function close() { }

    public function commitAll()
    {
        // TODO: Implement commitAll() method.
    }

    public function rollbackAll()
    {
        // TODO: Implement rollbackAll() method.
    }
}
