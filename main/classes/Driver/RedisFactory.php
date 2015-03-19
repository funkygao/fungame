<?php

namespace Driver;

final class RedisFactory {

    /**
     * @param string $pool
     * @param string $driverType
     * @return \Driver\Redis\Driver
     * @throws \ExpectedErrorException
     */
    public static function instance($pool = 'default', $driverType = 'PhpRedis') {
        static $drivers = array();
        if (!isset($drivers[$pool])) {
            $driverClass = '\\Driver\\Redis\\' . ucfirst($driverType);
            $drivers[$pool] = new $driverClass($pool);
        }

        return $drivers[$pool];
	}

}
