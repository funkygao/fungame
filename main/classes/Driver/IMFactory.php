<?php

namespace Driver;

/**
 * Intant Messaging.
 */
final class IMFactory {

    /**
     * @var \Driver\IM\Driver
     */
    private static $_driver = NULL;

    public static function instance($driver = 'pubnub') {
        if (self::$_driver === NULL) {
            $driverClass = '\\Driver\IM\\' . ucfirst($driver);
            self::$_driver = new $driverClass();
            self::$_driver->init();
        }

        return self::$_driver;
    }

}
