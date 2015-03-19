<?php

namespace Driver\Db\Mysql;

abstract class ShardConnectionFactory implements \Consts\ErrnoConst {

    /**
     * @return MysqlPDO
     * @throws \PDOException
     */
    protected abstract function connect();

    protected abstract function disconnect($key = '');

    /**
     * @var \System\Logger
     */
    private static $_logger;

    /**
     * @param \Driver\Db\ShardInfo $shardInfo
     * @return ShardConnectionFactory
     * @throws \NotImplementedException
     * @throws \ExpectedErrorException
     */
    public static function factory(\Driver\Db\ShardInfo $shardInfo) {
        static $instances = array();

        self::$_logger = \System\Logger::getLogger(get_called_class());
        $slug = $shardInfo->getSlug();
        if (!isset($instances[$slug])) {
            switch ($shardInfo->type()) {
                case 'just-master':
                case 'master-master':
                    $instances[$slug] = new ShardConnectionMaster($shardInfo);
                    break;

                case 'master-slave':
                    throw new \NotImplementedException();

                default:
                    throw new \ExpectedErrorException("Unknown ShardInfo: " . var_export($shardInfo, 1),
                        self::ERRNO_SYS_INVALID_ARGUMENT);
            }
        }

        return $instances[$slug];
    }

    /**
     * execute a query, prepare, bind, execute while supressing visiable errors
     *
     * @param string $query
     * @param array $args
     * @return \Driver\Db\DbResult
     */
    public final function query($query, $args = array()) {
        $connection = $this->connect();
        $stmt = $connection->prepare($query); //throws PDOException
        $stmt->execute($args);

        $data = array();
        if (stripos(ltrim($query), 'SELECT') === 0) {
            $data = $stmt->fetchAll(); // just buffer in PHP

            // explain this query
            if (\System\Config::isDebugMode()) {
                $explain = $connection->prepare("EXPLAIN $query");
                $explain->execute($args);
                self::$_logger->debug('explain', array(
                    'query' => $query,
                    'args' => $args,
                    'result' => $explain->fetchAll(), // rows, select_type, Extra, ref, key_len, possible_keys, key, table, type
                ));
            }
        }

        $result = new \Driver\Db\DbResult();
        $result->setResults($data);
        $result->setAffectedRows($stmt->rowCount());
        $result->setInsertId($connection->lastInsertId());

        return $result;
    }

}
