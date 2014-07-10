<?php

namespace Driver\Db;

use \PDO;

/**
 * Concreate Connection to a databse
 *
 * @author Dathan Vance Pattishall 
 */
class ShardConnectionMaster extends ShardConnectionFactory {

    private $Host = null;
    private $Port = 3306;
    private $User = null;
    private $Pass = null;
    private $DB = null;

    protected $shardInfo;

    protected static $connections;

    const SEPARATOR = ':';

    public function __construct(ShardInfo $shardInfo) {
        $this->shardInfo = $shardInfo;
        $this->Host = $shardInfo->getHosts()[0];
        $this->User = $shardInfo->getDatabaseUser();
        $this->DB   = $shardInfo->getDatabaseName();
        $this->Pass = $shardInfo->getDatabasePassword();
    }

    private function getConnKey() {
        return $this->Host . self::SEPARATOR . $this->DB . self::SEPARATOR . $this->Port;
    }

    public function isConnected() {
        return isset(self::$connections[$this->getConnKey()]);
    }

    /**
     *
     * @throws \PDOException|\Exception
     * @return MysqlPDO
     */
    public function connect() {
        if ($this->isConnected()) {
            return self::$connections[$this->getConnKey()];
        }

        try {
            $dsn = "mysql:host={$this->Host};dbname={$this->DB};charset=utf8";
            $currentConnection = new MysqlPDO($dsn, $this->User, $this->Pass);
            $currentConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $currentConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $currentConnection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
            $currentConnection->setAttribute(PDO::ATTR_TIMEOUT, 4);
            $currentConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NAMED);
        } catch (\PDOException $e) {
            //@TODO catch the exception if it's Lost Connection to Server Reconnect
            throw $e;
        }

        self::$connections[$this->getConnKey()] = $currentConnection;
        return $currentConnection;
    }

    public function disconnect($key = '') {
        if (self::$connections) {
            if ($key) {
                unset(self::$connections[$key]);
                return;
            }
            unset(self::$connections[$this->getConnKey()]);
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
