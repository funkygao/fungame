<?php

namespace Driver;

/**
 * Message Queue.
 */
final class MQFactory {

    /**
     * @param string $tube
     * @param string $driver
     * @return \Driver\MQ\Driver
     */
    public static function instance($tube = 'default', $driver = 'beanstalk') {
        static $drivers = array();
        $key = "$driver:$tube";
        if (!isset($drivers[$key])) {
            $driverClass = '\\Driver\MQ\\' . ucfirst($driver);
            $drivers[$key] = new $driverClass();
            $drivers[$key]->init($tube);
        }

        return $drivers[$key];
    }

}
