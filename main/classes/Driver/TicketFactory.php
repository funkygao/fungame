<?php

namespace Driver;

final class TicketFactory {

    /**
     * @var \Driver\Ticket\Driver
     */
    private static $_driver = NULL;

    /**
     * @param string $driver
     * @return Ticket\Driver
     */
    public static function instance($driver = 'DB') {
        if (self::$_driver === NULL) {
            $driverClass = '\\Driver\Ticket\\' . ucfirst($driver);
            self::$_driver = new $driverClass();
        }

        return self::$_driver;
    }

}
