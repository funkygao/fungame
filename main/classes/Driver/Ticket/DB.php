<?php

namespace Driver\Ticket;

class DB implements Driver {

    // Use mysql auto_increment field to generate next id
    public function nextId($tablePrefix) {
        $sc = \Driver\DbFactory::ticketsPool()->query("{$tablePrefix}Tickets",
            0, "REPLACE INTO {$tablePrefix}Tickets SET stub=1", array());
        $ticketId = $sc->getInsertId();
        if ($ticketId <= 0) {
            throw new ShardTicketException("{$tablePrefix}Tickets are down");
        }

        return (int) $ticketId;
    }

}

class ShardTicketException extends \Exception {}
