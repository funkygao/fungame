<?php

namespace Driver;

/**
 * Intant Messaging.
 *
 * Currently, only supports pubnub | rtm. Either works, not both.
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

    public static function ifUseRtm() {
        return \System\Config::get('global', 'im') == 'rtm';
    }

}
