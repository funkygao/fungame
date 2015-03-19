<?php

namespace Driver\Db;

/**
 * <pre>
 *
 *          Driver
 *             |
 *    +-------------------+
 *    |                   |
 *   Fae                Mysql
 *
 * </pre>
 */
interface Driver {

    /**
     * @param string $pool e,g. 'user' instead of 'UserShard', see DbConst
     * @param string $table TODO maybe this param is uneccessary
     * @param int $hintId
     * @param string $sql
     * @param array $args
     * @param string $cacheKey TODO maybe should be list, update will modify several caches
     *
     * @return \Driver\Db\DbResult
     */
    public function query($pool, $table, $hintId, $sql, array $args = array(),
                          $cacheKey = '');

    /**
     * Query accross all shards for a given table.
     *
     * ATTENTION: Exec not allowed!
     *
     * You can only use SELECT, UPDATE/DELETE/INSERT not allowed!
     *
     * @param string $pool
     * @param string $table
     * @param string $sql
     * @param array $args
     * @return \Driver\Db\DbResult
     */
    public function queryShards($pool, $table, $sql, array $args = array());

    /**
     * Do the cleanup job.
     *
     * e,g. close db connection.
     */
    public function close();

    public function commitAll();

    public function rollbackAll();

}
