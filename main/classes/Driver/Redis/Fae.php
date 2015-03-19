<?php

namespace Driver\Redis;

final class Fae
    implements Driver {

    /**
     * @var string
     */
    private $_pool;

    /**
     * @var \fun\rpc\FunServantClient
     */
    private static $_faeClient = NULL;

    /**
     * @var \fun\rpc\Context
     */
    private static $_faeCtx = NULL;

    /**
     * @param string $pool
     */
    public function __construct($pool) {
        $this->_pool = $pool;

        if (NULL === self::$_faeClient) {
            self::$_faeClient = \FaeEngine::client();
            self::$_faeCtx = \FaeEngine::ctx();
        }
    }

    /**
     * Magic method to call the many many redis commands.
     *
     * jsonize data to string before you use value as arguments.
     *
     * Usage:
     *   \Driver\RedisFactory::instance('default', 'fae')
     *        ->set('the key', 'the value');
     *
     *  \Driver\RedisFactory::instance('default', 'fae')
     *        ->get('the key');
     *
     * @param string $redisCmd not case sensitive, redis.SET==redis.set=redis.sEt
     * @param array $keysAndArgs [key, args...]
     * @return string SerializedData
     */
    public function __call($redisCmd, $keysAndArgs) {
        // fae.rd_call($ctx, $cmd, $pool, $keysAndArgs)
        if ($redisCmd == 'multi') {
            // empty $keysAndArgs
        }
        $redisArgs = array();
        $redisArgs[] = self::$_faeCtx;
        $redisArgs[] = $redisCmd;
        $redisArgs[] = $this->_pool;
        $redisArgs[] = $keysAndArgs;
        return call_user_func_array(array(self::$_faeClient, 'rd_call'),
            $redisArgs);
    }

    public function set($key, $value) {
        $ok = $this->__call('set', array($key, $value));
        return $ok == 'OK';
    }

    public function hgetall($key) {
        $val = $this->__call('hgetall', array($key));
        // ["13","{\"march_id\":1,\"uid\":11}","24","{\"march_id\":2,\"uid\":22}"]
        $decodedVal = json_decode($val, TRUE);
        print_r($decodedVal);exit;
        $ret = array();
        for ($i = 0; $i < count($decodedVal); $i+=2) {
            $ret[$decodedVal[$i]] = $decodedVal[$i + 1];
        }
        return $ret;
    }

}
