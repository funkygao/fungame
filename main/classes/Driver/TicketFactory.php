<?php

namespace Driver;

final class TicketFactory {

    /**
     * @var \Driver\Ticket\Driver[]
     */
    private static $_drivers = array();

    /**
     * @param string $driver
     * @return Ticket\Driver
     */
    public static function instance($driver = 'DB') {
        if (!isset(self::$_drivers[$driver])) {
            $driverClass = '\\Driver\Ticket\\' . ucfirst($driver);
            self::$_drivers[$driver] = new $driverClass();
        }

        return self::$_drivers[$driver];
    }

}
