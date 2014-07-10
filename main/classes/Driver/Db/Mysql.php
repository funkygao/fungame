<?php

namespace Driver\Db;

class Mysql implements Driver, \Consts\DbConst {

    protected $cachePool = 'default';
    protected $cacheLifeTime = 3600;

    private $_pool = '';

    private static $_userLookupCache = array();
    private static $_allianceLookupCache = array();
    private static $_worldLookupCache = array();
    private static $_chatLookupCache = array();

    public function __construct($pool) {
        $this->_pool = $pool;
    }

    public function setCachePool($pool) {
        $this->cachePool = $pool;
        return $this;
    }

    protected function getCachePool() {
        return $this->cachePool;
    }

    public function setCacheLifeTime($lt) {
        $this->cacheLifeTime = $lt;
        return $this;
    }

    protected function getCacheLifeTime() {
        return $this->cacheLifeTime;
    }

    private function userShard($uid) {
        if (isset(self::$_userLookupCache[$uid])) {
            $row = self::$_userLookupCache[$uid];
        } else {
            $row = $this->lookupShard($uid, 'UserLookup');
            self::$_userLookupCache[$uid] = $row;
        }
        return MysqlConnection::factory(self::POOL_USER, $row["shardId"]);
    }

    private function allianceShard($allianceId) {
        if (isset(self::$_allianceLookupCache[$allianceId])) {
            $row = self::$_allianceLookupCache[$allianceId];
        } else {
            $row = $this->lookupShard($allianceId, 'AllianceLookup');
            self::$_allianceLookupCache[$allianceId] = $row;
        }

        return MysqlConnection::factory(self::POOL_ALLIANCE, $row["shardId"]);
    }

    private function worldShard($worldId) {
        if (isset(self::$_worldLookupCache[$worldId])) {
            $row = self::$_worldLookupCache[$worldId];
        } else {
            $row = $this->lookupShard($worldId, 'WorldLookup');
            self::$_worldLookupCache[$worldId] = $row;
        }

        return MysqlConnection::factory(self::POOL_WORLD, $row["shardId"]);
    }

    private function chatShard($roomId) {
        if (isset(self::$_chatLookupCache[$roomId])) {
            $row = self::$_chatLookupCache[$roomId];
        } else {
            $row = $this->lookupShard($roomId, 'ChatLookup');
            self::$_chatLookupCache[$roomId] = $row;
        }
        return MysqlConnection::factory(self::POOL_CHAT, $row["shardId"]);
    }

    private function ticketsShard() {
        return MysqlConnection::factory(self::POOL_TICKETS);
    }

    private function lookupShard($entityId, $table) {
        return ShardLookup::factory($table)->mapLookup($entityId);
    }

    private function unlinkShard($entityId, $table) {
        return ShardLookup::factory($table)->mapDelete($entityId);
    }

    private function getRangeShardId($id) {
        return ShardLookup::factory("")->hashRangeLookup($id);
    }

    private function globalShard() {
        $this->setPool(self::POOL_MAIN);
        $configStruct = \System\Config::get('struct');
        $idShard = 1;
        //vz dbmapDbItem && other idmapDbItem
        $key = isset($configStruct['idmapDbItem']) ? 'idmapDbItem' : (isset($configStruct['dbmapDbItem']) ? 'dbmapDbItem' : '');
        if ($key) {
            $i = str_replace('db', '', $configStruct[$key]);
            if (is_numeric($i)) {
                $idShard = $i;
            }
        }

        $shard = MysqlConnection::factory(self::POOL_MAIN); // the global shard is always on the 1st shard
        $shard->setCacheLifeTime($this->getCacheLifeTime());
        $shard->setCachePool($this->getCachePool());

        return $shard;
    }

    private function tools() {
        return MysqlConnection::factory(self::POOL_TOOLS);
    }

    private function lookupConn() {
        return MysqlConnection::factory(self::POOL_LOOKUP);
    }

    /**
     * @param string $table DB pool name
     * @param int $hintId
     * @param string $sql
     * @param array $args
     * @return array|mixed Array of rows
     */
    public function query($table, $hintId, $sql, array $args = array()) {
        return $this->_getShardByHintId($hintId)->query($sql, $args);
    }

    public function close() {
        MysqlConnection::cleanUp();
    }

    /**
     * @param int $hintId
     * @return MysqlConnection
     */
    private function _getShardByHintId($hintId) {
        switch ($this->_pool) {
            case self::POOL_ALLIANCE:
                return $this->allianceShard($hintId);
            case self::POOL_USER:
                return $this->userShard($hintId);
            case self::POOL_WORLD:
                return $this->worldShard($hintId);
            case self::POOL_CHAT:
                return $this->chatShard($hintId);
            case self::POOL_TOOLS:
                return $this->tools();
            case self::POOL_TICKETS:
                return $this->ticketsShard();
            case self::POOL_LOOKUP:
                return $this->lookupConn();
            default:
                return $this->globalShard();
        }
    }
}

class ShardTicketException extends \Exception {}
