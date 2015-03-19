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
            try {
                $drivers[$key] = new $driverClass();
                $drivers[$key]->init($tube);
            } catch (\Pheanstalk_Exception_ConnectionException $ex) {
                // beanstalkd died, fallback to dummy driver
                // we shouldn't let beanstalkd kill us
                $drivers[$key] = new \Driver\MQ\Dummy();
                $drivers[$key]->init($tube);
            }

        }

        return $drivers[$key];
    }

}
