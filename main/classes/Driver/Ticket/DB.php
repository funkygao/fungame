<?php

namespace Driver\Ticket;

class DB
    implements Driver, \Consts\DbConst {

    // Use mysql auto_increment field to generate next id
    public function nextId($tablePrefix) {
        $sc = \Driver\DbFactory::instance()
            ->query(self::POOL_TICKETS, "{$tablePrefix}Tickets",
            0, "REPLACE INTO {$tablePrefix}Tickets SET stub=1");
        $ticketId = $sc->getInsertId();
        if ($ticketId <= 0) {
            throw new ShardTicketException("{$tablePrefix}Tickets are down");
        }

        return (int) $ticketId;
    }

    // FIXME the 2 queries are not atomic
    public function nextIds($tablePrefix, $count) {
        $db = \Driver\DbFactory::instance();
        $db->query(self::POOL_TICKETS, "{$tablePrefix}Tickets",
                0, "UPDATE {$tablePrefix}Tickets SET id=id+?", array($count));
        $sc = $db->query(self::POOL_TICKETS, "{$tablePrefix}Tickets",
            0, "REPLACE INTO {$tablePrefix}Tickets SET stub=1");
        $endId = $sc->getInsertId() - 1;
        if ($endId <= 0) {
            throw new ShardTicketException("{$tablePrefix}Tickets are down");
        }

        return array((int)$endId - $count, (int)$endId);
    }

    /**
     * @param string $tag
     * @return int
     */
    public function nextIdWithTag($tag) {
        throw new \NotImplementedException();
    }

    /**
     * @param int $id
     * @return array [ts, tag, wid, seq]
     */
    public function decodeId($id) {
        throw new \NotImplementedException();
    }

}

class ShardTicketException extends \Exception {}
