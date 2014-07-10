<?php

namespace Driver\Db;

/**
 * <pre>
 *
 *          Driver
 *             |
 *    +-------------------+
 *    |        |          |
 *   Fae      Mock      Mysql
 *
 * </pre>
 */
interface Driver
{
    /**
     * @param string $table
     * @param int $hintId
     * @param string $sql
     * @param array $args
     * @return \Driver\DbResult
     */
    public function query($table, $hintId, $sql, array $args = array());

    /**
     * Do the cleanup job.
     *
     * e,g. close db connection.
     */
    public function close();
}
