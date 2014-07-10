<?php

namespace Driver\Db;

use \Exception,
    Driver\CacheFactory;

class MysqlConnection {

    private static $TRANSACTION_START = 0;

    private static $instances = array();

    protected $cacheLifeTime = 3600;
    protected $cachePool = 'default';

    protected $shardInfo; // work as protected $pool, $shardId, $slug

    /**
     * @param ShardInfo $shardInfo
     * @return \Driver\Db\MysqlConnection
     */
    protected function __construct(ShardInfo $shardInfo)
    {
        $this->shardInfo = $shardInfo;
    }

    /**
     * return 1 instance of the pool/role type shard - the underlying connection
     * may be the same if master == slave for
     *
     * @param string $pool
     * @param int $shardId
     * @return MysqlConnection
     */
    public static function factory($pool, $shardId = 0)
    {
        $info = ShardInfo::factory($pool, $shardId);
        $key = $info->getSlug();
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        return (self::$instances[$key] = new self($info));
    }

    /**
     *  wrap code in a transaction
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    public function transactionHandler($callback)
    {
        try {
            $this->begin();
            $ret = $callback();
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
        return $ret;
    }

    private function connection()
    {
        return ShardConnectionFactory::factory($this->shardInfo);
    }

    /**
     * get the config of the shard
     *
     * @return ShardInfo
     */
    public function getShardInfo()
    {
        return $this->shardInfo;
    }

    /**
     * don't actually connect until you query
     *
     * @param string $sql
     * @param array $args
     * @param string $cacheKey
     * @throws \InvalidArgumentException
     * @return \Driver\DbResult
     */
    public function query($sql, $args = array(), $cacheKey = '')
    {
        if ($args && !is_array($args)) {
            throw new \InvalidArgumentException("Args if set must be an array holding the bind order");
        }

        $ret = $this->connection()->query($sql, $args);

        if ($cacheKey && ($ret->getNumRows() || $ret->getAffectedRows())) {
            CacheFactory::instance($this->getCachePool())->delete($cacheKey);
        }

        return $ret;
    }

    /**
     * using a builder design pattern set the cache pool
     * @param string $pool
     * @return MysqlConnection
     */
    public function setCachePool($pool)
    {
        $this->cachePool = $pool;
        return $this;
    }

    /**
     * return the cache pool
     * @return string
     */
    public function getCachePool()
    {
        return $this->cachePool;
    }

    public function setCacheLifeTime($lt)
    {
        $this->cacheLifeTime = $lt;
        return $lt;
    }

    public function getCacheLifeTime()
    {
        return $this->cacheLifeTime;
    }

    /**
     * helper function to return an array of stdClass
     * @param string $query
     * @param array $args
     * @param string $cacheKey
     * @return \ArrayObject List of hashmap
     */
    public function select($query, $args = array(), $cacheKey = '')
    {
        if ($cacheKey && $ret = CacheFactory::instance($this->getCachePool())->get($cacheKey)) {
            return $ret;
        }

        $ret = $this->query($query, $args)->getResults();

        if ($cacheKey) {
            CacheFactory::instance($this->getCachePool())->set($cacheKey,
                $ret, $this->getCacheLifeTime());
        }
        return $ret;
    }

    /**
     * @param string $query
     * @param array $args
     * @param string $cacheKey
     * @return array hashmap
     */
    public function selectRow($query, $args = array(), $cacheKey = '')
    {
        if ($cacheKey && $ret = CacheFactory::instance($this->getCachePool())->get($cacheKey)) {
            return isset($ret[0]) ? $ret[0] : NULL;
        }

        $ret = $this->query($query, $args)->getResults();

        if ($cacheKey) {
            CacheFactory::instance($this->getCachePool())->set($cacheKey,
                $ret, $this->getCacheLifeTime());
        }

        return isset($ret[0]) ? $ret[0] : NULL;
    }

    public function insert($table, $key_value, $ignore = false, $onDupe = array(), $cacheKey = '')
    {
        $query = "INSERT INTO $table";
        if ($ignore) {
            $query = "INSERT IGNORE INTO $table";
        }

        list($cols, $vals, $args) = $this->__getBind($key_value);

        $query .= " (" . join(",", $cols) . ") VALUES(" . join(",", $vals) . ")";

        if ($onDupe) {

            if (!is_array($onDupe)) {

                $dupeStr = $onDupe;
            } else {

                list($cols, $vals, $dupeArgs) = $this->__getBind($onDupe);
                $toJoin = array();
                foreach ($cols as $pos => $col) {
                    $toJoin[] = $col . '=' . $vals[$pos];
                }

                $dupeStr = implode(",", $toJoin);
                $args = array_merge($args, $dupeArgs);
            }

            $query .= " ON DUPLICATE KEY UPDATE $dupeStr";
        }

        return $this->query($query, $args, $cacheKey);
    }

    /**
     * helper function to generate replace sql
     * @param string $table
     * @param array $key_value
     * @param string $cacheKey
     * @return \Driver\DbResult
     */
    public function replace($table, $key_value, $cacheKey = '')
    {
        $query = "REPLACE INTO $table";

        list($cols, $vals, $args) = $this->__getBind($key_value);

        $query .= " (" . join(",", $cols) . ") VALUES(" . join(",", $vals) . ")";

        return $this->query($query, $args, $cacheKey);
    }

    public function batchInsert($table, $cols, $values, $cacheKey = '')
    {
        if (!is_array($values[0])) {
            throw new \InvalidArgumentException("Values is not of the correct format");
        }

        $args = array();
        $inserts = array();
        foreach ($values as $pos => $key_value) {
            list($cs, $vals, $bs) = $this->__getBind($key_value);

            if (sizeof($cols) != sizeof($cs)) {
                throw new \InvalidArgumentException("Values are malformed. Position $pos of array is not correct");
            }
            $args = array_merge($args, $bs);
            $inserts[] = "(" . join(",", $vals) . ")";
        }

        $query = "INSERT INTO $table (" . join(',', $cols) . ") VALUES " . join(',', $inserts);

        return $this->query($query, $args, $cacheKey);
    }

    public function update($table, array $set, $where, array $where_args, $cacheKey = '')
    {
        list($cols, $vals, $args) = $this->__getBind($set);

        $setInfo = array();
        foreach ($cols as $pos => $bind) {
            $setInfo[] = "$bind=" . $vals[$pos];
        }

        $setStr = join(",", $setInfo);
        if (!$setStr) {
            throw new \Exception("There is no setStr this will produce a Syntax Error\n");
        }

        $query = "UPDATE $table SET " . $setStr . " WHERE $where";

        if ($where_args) {
            $args = array_merge($args, $where_args);
        }
        return $this->query($query, $args, $cacheKey);
    }

    public function delete($table, $where, $where_args, $cacheKey = '')
    {
        $query = "DELETE FROM $table WHERE $where";
        return $this->query($query, $where_args, $cacheKey);
    }

    private function __getBind($key_value)
    {
        $cols = array();
        $args = array();
        $value = array();

        foreach ($key_value as $key => $value) {
            $cols[] = $key;

            if (is_null($value)) {
                $vals[] = '\N';
            } else if (strpos($value, "NOW()") !== FALSE) {
                $vals[] = 'NOW()';
            } else {
                $vals[] = '?';
                $args[] = $value;
            }
        }

        return array($cols, $vals, $args);
    }

    public function begin()
    {
        self::$TRANSACTION_START++;
        $this->query('START TRANSACTION');
    }

    public function commit()
    {
        self::$TRANSACTION_START--;

        if (self::$TRANSACTION_START == 0) {
            $this->query('COMMIT');
            return;
        }
        if (self::$TRANSACTION_START < 0) {
            throw new ShardTransactionException("Dude WTF a transaction was not started");
        }
        return;
    }

    public function rollback()
    {
        $error = $this->error();
        $this->query("ROLLBACK");
        if ($error) {
            throw new ShardTransactionException("RolledBack: $error");
        }

        return;
    }

    /**
     * cleanup
     */
    public static function cleanUp()
    {
        if (self::$instances) {
            foreach (self::$instances as $key => $shardObj) {
                $shardObj->connection()->disconnect();
            }
        }
    }

}

class ShardTransactionException extends \Exception
{

}

class ShardQueryException extends \Exception
{

}
