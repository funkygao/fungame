<?php

namespace Driver\Db;

/**
 * Shard Connection Factory
 *
 * Abstraction Layer on how to connect to a given Shard Type how do you connect to it, failover, and query.
 * The subclasses define the logic. This uses an Abstract Factory Design
 * Pattern. http://en.wikipedia.org/wiki/Abstract_factory_pattern
 *
 * @author Dathan Vance Pattishall
 */
abstract class ShardConnectionFactory
{

    /**
     * The list of static connections pooled for each request
     *
     * @var mysqli[]
     */
    protected static $connections = array();

    private static $instance = array();

    /**
     * @var \System\Logger
     */
    private static $_logger;

    public static function factory(ShardInfo $shardInfo) {
        $slug = $shardInfo->getSlug();
        if (isset(self::$instance[$slug])) {
            return self::$instance[$slug];
        }

        self::$_logger = \System\Logger::getLogger(get_called_class());
        switch ($shardInfo->type()) {
            case 'just-master':
                return (self::$instance[$slug] = new ShardConnectionMaster($shardInfo));
            case 'master-master':
                return (self::$instance[$slug] = new ShardConnectionMasterMaster($shardInfo));
            case 'master-slave':
                return (self::$instance[$slug] = new ShardConnectionMasterSlave($shardInfo));
            default:
                throw new \InvalidArgumentException("Unknown ShardInfo: " . var_export($shardInfo, 1));
        }

    }

    /**
     * execute a query, prepare, bind, execute while supressing visiable errors
     *
     * @param string $query
     * @param array $args
     * @return \Driver\DbResult
     */
    public function query($query, $args = array()) {
        $connection = $this->connect(); // throws PDOException
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

        $result = new \Driver\DbResult();
        $result->setResults($data);
        $result->setAffectedRows($stmt->rowCount());
        $result->setInsertId($connection->lastInsertId());

        return $result;
    }

    /**
     * @return MysqlPDO
     */
    abstract public function connect();

    abstract public function disconnect($key = '');
}
