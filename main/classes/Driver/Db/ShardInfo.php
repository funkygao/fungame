<?php

namespace Driver\Db;

final class ShardInfo
    implements \Consts\DbConst, \Consts\ErrnoConst {

    /**
     * @var array
     */
    private $config;

    private $pool;
    private $shardId;

    private $isMasterMaster = 0;
    private $isMasterSlave = 0;

    private static $_poolShardMappings = array(
        // non-sharded
        self::POOL_TOOLS => array('tools'),
        self::POOL_LOOKUP => array('ShardLookup'),
        self::POOL_GLOBAL => array('Global'),
        self::POOL_TICKETS => array('Tickets'),

        // sharded
        self::POOL_USER => array('UserShard', 'UserLookup'),
        self::POOL_ALLIANCE => array('AllianceShard', 'AllianceLookup'),
        self::POOL_WORLD => array('WorldShard', 'WorldLookup'),
        self::POOL_CHAT => array('ChatShard', 'ChatLookup'),
    );

    private function __construct($pool, $shardId = 0) {
        list($shardName, $needShardLookup, $_) = self::pool2shard($pool);
        if (!$needShardLookup) {
            $shardId = '';
        }

        // read database config file and validate
        $this->config = \System\Config::get('database', $shardName . $shardId);
        if (!$this->config) {
            throw new \ExpectedErrorException("Cannot find db config for: $pool:$shardId", self::ERRNO_SYS_INVALID_ARGUMENT);
        }
        if (!isset($this->config['database']) || !isset($this->config['host'])
            || !isset($this->config['username']) || !isset($this->config['password'])) {
            throw new \ExpectedErrorException("Invalid db config: " . json_encode($this->config), self::ERRNO_SYS_INVALID_ARGUMENT);
        }

        $this->pool = $pool;
        $this->shardId = $shardId;
    }

    public static function pool2shard($pool) {
        $shard = self::$_poolShardMappings[$pool];
        if (!$shard) {
            throw new \ExpectedErrorException("Unexpected pool ($pool)", self::ERRNO_SYS_INVALID_ARGUMENT);
        }

        $needShardLookup = FALSE;
        $shardLookupTable = '';
        if (count($shard) > 1) {
            $needShardLookup = TRUE;
            $shardLookupTable = $shard[1];
        }

        return array($shard[0], $needShardLookup, $shardLookupTable);
    }

    /**
     * @param string $pool
     * @param int $shardId
     * @return ShardInfo
     */
    public static function factory($pool, $shardId) {
        static $instances = array();
        $key = "{$pool}-{$shardId}";
        if (!isset($instances[$key])) {
            $instances[$key] = new self($pool, $shardId);
        }

        return $instances[$key];
    }

    public function getConfig() {
        return $this->config;
    }

    public function getSlug() {
        return "{$this->pool}-{$this->shardId}";
    }

    public function getDatabaseName() {
        return $this->config['database'];
    }

    /**
     * tests the type
     *
     * @return string
     */
    public function type() {
        if ($this->isMasterMaster == 0 && $this->isMasterSlave == 0) {
            return 'just-master';
        }

        if ($this->isMasterMaster) {
            return 'master-master';
        }

        if ($this->isMasterSlave) {
            return 'master-slave';
        }

        return 'unknown';
    }

}
