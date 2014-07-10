<?php

namespace Driver\Db;

class Fae implements Driver {

    private static $_pools = array(
        // shard lookup
        'AllianceLookup' => 'ShardLookup',
        'UserLookup' => 'ShardLookup',
        'WorldLookup' => 'ShardLookup',
        'AllianceTickets' => 'Tickets',
        'JobTickets' => 'Tickets',
        'Tickets' => 'Tickets',

        // user shard
        'UserInfo' => 'UserShard', // table => pool
        'UserCity' => 'UserShard',
        'Job' => 'UserShard',
        'UserInventory' => 'UserShard',
        'UserStats' => 'UserShard',
        'CityMap' => 'UserShard',
        'UserHero' => 'UserShard',
        'Mail' => 'UserShard',
        'March' => 'UserShard',
        'PVEMarch' => 'UserShard',
        'Progressions' => 'UserShard',
        'Research' => 'UserShard',
        'UserChatGroup' => 'UserShard',
        'UserChatRoom' => 'UserShard',
        'UserConsumable' => 'UserShard',
        'UserContacts' => 'UserShard',
        'UserQuest' => 'UserShard',

        // alliance shard
        'Alliance' => 'AllianceShard',
        'AllianceHelp' => 'AllianceShard',
        'AllianceRoster' => 'AllianceShard',
        'DiplomacyFrom' => 'AllianceShard',
        'DiplomacyTo' => 'AllianceShard',

        // world shard
        'WorldMap' => 'WorldShard',
        // TODO March is in both world shard and user shard

        // TODO more to be added here
    );

    /**
     * @param string $table
     * @param int $hintId
     * @param string $sql
     * @param array $args
     * @throws \InvalidArgumentException
     * @return \Driver\DbResult
     */
    public function query($table, $hintId, $sql, array $args = array()) {
        if (!isset(self::$_pools[$table])) {
            throw new \InvalidArgumentException("Table: $table pool is undefined");
        }

        $mysqlResult = \FaeEngine::client()->my_query(
            \FaeEngine::ctx(),
            self::$_pools[$table], // pool name
            $table,
            $hintId,
            $sql,
            $args
        );

        $result = new \Driver\DbResult();
        $result->setAffectedRows($mysqlResult->rowsAffected);
        $result->setInsertId($mysqlResult->lastInsertId);
        if (!$mysqlResult->rowsAffected) {
            // SELECT
            $rows = json_decode($mysqlResult->rows, TRUE);
            if (!empty($rows['vals'])) {
                $result->setResults($this->_fetch_assoc_rows($rows['cols'], $rows['vals']));
            }
        }

        return $result;
    }

    private function _fetch_assoc_rows($columns, $rows) {
        $ret = array();
        foreach ($rows as $row) {
            $assocRow = array();
            $i = 0;
            foreach ($row as $val) {
                $assocRow[$columns[$i]] = $val;
                $i++;
            }

            $ret[] = $assocRow;
        }

        return $ret;
    }

    public function close() {

    }
}