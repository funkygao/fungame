<?php

namespace Driver\Db\Mysql;

final class ShardConnectionMaster extends ShardConnectionFactory {

    private $Host = null;
    private $Port = 3306;
    private $User = null;
    private $Pass = null;
    private $DB = null;

    private $shardInfo;

    private static $connections;

    public function __construct(\Driver\Db\ShardInfo $shardInfo) {
        $this->shardInfo = $shardInfo;

        $config = $shardInfo->getConfig();
        $this->Host = $config['host'];
        $this->User = $config['username'];
        $this->Pass = $config['password'];
        $this->DB = $config['database'];
    }

    private function _connKey() {
        return "{$this->Host}:{$this->Port}:{$this->DB}";
    }

    /**
     *
     * @throws \PDOException|\Exception
     * @return MysqlPDO
     */
    protected function connect() {
        $key = $this->_connKey();
        if (!isset(self::$connections[$key])) {
            try {
                $dsn = "mysql:host={$this->Host};port={$this->Port};dbname={$this->DB};charset=utf8";
                $currentConnection = new MysqlPDO($dsn, $this->User, $this->Pass, array(
                    MysqlPDO::ATTR_AUTOCOMMIT => true,
                    MysqlPDO::ATTR_TIMEOUT => 4,
                )); // will connect db
                // TODO enhance the configs
                // we don't use PDO persistent connection
                // http://stackoverflow.com/questions/3332074/what-are-the-disadvantages-of-using-persistent-connection-in-pdo
                $currentConnection->setAttribute(MysqlPDO::ATTR_ERRMODE, MysqlPDO::ERRMODE_EXCEPTION); // 出现错误时抛出PDOException
                $currentConnection->setAttribute(MysqlPDO::ATTR_EMULATE_PREPARES, false);
                $currentConnection->setAttribute(MysqlPDO::ATTR_STRINGIFY_FETCHES, true);
                $currentConnection->setAttribute(MysqlPDO::ATTR_DEFAULT_FETCH_MODE, MysqlPDO::FETCH_NAMED);
            } catch (\PDOException $ex) {
                // TODO catch the exception if it's Lost Connection to Server Reconnect
                throw $ex;
            }

            self::$connections[$key] = $currentConnection;
        }

        return self::$connections[$key];
    }

    public function disconnect($key = '') {
        if (self::$connections) {
            if ($key) {
                unset(self::$connections[$key]);
                return;
            }
            unset(self::$connections[$this->_connKey()]);
        }
    }

    public function disconnectAll() {
        if (self::$connections) {
            foreach (self::$connections as $key => $conn) {
                unset(self::$connections[$key]);
            }
        }
    }

}
