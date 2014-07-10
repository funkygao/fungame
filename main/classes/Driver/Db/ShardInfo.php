<?php

namespace Driver\Db;

/**
 * From siteConfig build the ShardInfo Object. Since the format is different
 * between database roles, this unifies the exposure - allowing to change of
 * the source Config without messing up the app.
 *
 * @author Dathan Vance Pattishall
 */
class ShardInfo
{

    private $config = NULL;

    protected $pool = NULL;
    protected $shardId = 0;
    protected $slug = NULL;

    protected $isMasterMaster = 0;
    protected $isMasterSlave = 0;

    public function getSlug()
    {
        return $this->slug;
    }

    public function getPool()
    {
        return $this->pool;
    }

    public function getSID()
    {
        return $this->shardId;
    }

    /**
     * constructor - turns the source config into ShardInfo
     *
     * @param string $pool
     * @param int $shardId
     * @throws \InvalidArgumentException
     */
    protected function __construct($pool, $shardId = 0)
    {
        static $poolNameMappings = array(
            'tools' => 'tools',
            'lookup' => 'ShardLookup',
            'user' => 'UserShardN',
            'alliance' => 'AllianceShardN',
            'world' => 'WorldShardN',
            'chat' => 'ChatShardN',
            'tickets' => 'Tickets',
        );
        $poolName = $poolNameMappings[$pool];
        if (!$poolName) {
            throw new \InvalidArgumentException("Not a valid pool ($pool)");
        }
        if ($poolName[strlen($poolName) - 1] == 'N') {
            $poolName = substr($poolName, 0, strlen($poolName) - 1) . $shardId;
        }
        $this->config = \System\Config::get('database', $poolName);
        $this->pool = $pool;
        $this->shardId = $shardId;
        $this->slug = "{$pool}-{$shardId}";
    }

    protected static $_instances = array();

    /**
     * return 1 instance of the pool/role type shard - the underlying connection
     * may be the same if master == slave for
     *
     * @param string $pool
     * @param int $shardId
     * @return ShardInfo
     */
    public static function factory($pool, $shardId)
    {
        $key = "{$pool}-{$shardId}";
        if (isset(self::$_instances[$key])) {
            return self::$_instances[$key];
        }

        $info = new self($pool, $shardId);

        self::$_instances[$info->getSlug()] = $info;

        return $info;

    }

    /**
     * sets various vars
     *
     * @return void
     */
    protected function init()
    {

    }

    public function getDatabaseName()
    {
        if (!$this->config['database']) {
            throw new \InvalidArgumentException("Database was not set for Pool: {$this->pool} and ShardId: {$this->shardId}");
        }

        return $this->config['database'];
    }

    public function getHosts()
    {
        if (isset($this->config['host'])) {
            return array($this->config['host']);
        }

        throw new \InvalidArgumentException("Host is not set for Pool: {$this->pool} and ShardId: {$this->shardId}");
    }

    /**
     * return the database user for the role
     *
     * @return string
     */
    public function getDatabaseUser()
    {
        return $this->config['username'];
    }

    /**
     * database password
     *
     * @return string
     */
    public function getDatabasePassword()
    {
        return $this->config['password'];
    }


    /**
     * tests the type
     *
     * @return string
     */
    public function type()
    {

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
