<?php

namespace Driver\Db;

/**
 * Class ShardLookup description
 * @author dathan
 */
class ShardLookup extends Mysql {

    protected static $instance = array();

    /**
     * @var string
     */
    private $table;

    /**
     * default constructor set table
     * @param string $table
     */
    public function __construct($table) {
        $this->setTable($table);
    }

    /**
     * interface to get the shard object
     * @param string $table
     * @return ShardLookup
     */
    public static function factory($table) {
        if (isset(static::$instance[$table])) {
            return static::$instance[$table];
        }
        return static::$instance[$table] = new self($table);
    }

    /**
     * set the table
     * @param string $table
     * @throws \InvalidArgumentException
     */
    private function setTable($table) {
        $this->table = $table;
    }

    /**
     * return the table even if its none
     * @return string
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * @param int $entityId
     * @return array
     * @throws EntityDoesNotExistException
     * @throws ShardLockException
     */
    public function mapLookup($entityId) {
        $row = MysqlConnection::factory("lookup")
            ->selectRow("SELECT * FROM " . $this->getTable() . " WHERE entityId = ?",
                array($entityId));

        if ($row && $row["shardLock"]) {
            throw new ShardLockException("This Entity is locked");
        }

        if (!$row) {
            throw new EntityDoesNotExistException("The entity {$this->getTable()}:$entityId does not exit.");
        }

        return $row;
    }

    public function mapDelete($entityId) {
        return MysqlConnection::factory("lookup")->delete($this->getTable(), "entityId = ?", array($entityId));
    }

    /**
     *
     * @param int $id
     * @return int
     */
    public function hashRangeLookup($id) {
        if ($id < 91613391575)
            return 1;
        if ($id >= 91613391575 && $id < 575617478762710049)
            return 1;
        if ($id >= 575617478762710049 && $id < 1151652970532347073)
            return 2;
        if ($id >= 1151652970532347073 && $id < 1728381697671314740)
            return 3;
        if ($id >= 1728381697671314740 && $id < 2305255992978150432)
            return 4;
        if ($id >= 2305255992978150432 && $id < 2879934343862663299)
            return 5;
        if ($id >= 2879934343862663299 && $id < 3456586097308066472)
            return 6;
        if ($id >= 3456586097308066472 && $id < 4034127590667991228)
            return 7;
        if ($id >= 4034127590667991228 && $id < 4610886495740908215)
            return 8;
        if ($id >= 4610886495740908215 && $id < 5188089445597040650)
            return 9;
        if ($id >= 5188089445597040650 && $id < 5764363206983886628)
            return 10;
        if ($id >= 5764363206983886628 && $id < 6340149158128856460)
            return 11;
        if ($id >= 6340149158128856460 && $id < 6917639753751591942)
            return 12;
        if ($id >= 6917639753751591942 && $id < 7494903696463075964)
            return 13;
        if ($id >= 7494903696463075964 && $id < 8070423312614934443)
            return 14;
        if ($id >= 8070423312614934443 && $id < 8645754150017603414)
            return 15;
        if ($id >= 8645754150017603414 && $id < 9222136639934948903)
            return 16;
        if ($id >= 9222136639934948903 && $id < 9797731625031589702)
            return 17;
        if ($id >= 9797731625031589702 && $id < 10374872366398685937)
            return 18;
        if ($id >= 10374872366398685937 && $id < 10951636964146180677)
            return 19;
        if ($id >= 10951636964146180677 && $id < 11527508592358295485)
            return 20;
        if ($id >= 11527508592358295485 && $id < 12104380263636744674)
            return 21;
        if ($id >= 12104380263636744674 && $id < 12682234005971217988)
            return 22;
        if ($id >= 12682234005971217988 && $id < 13259221689642660934)
            return 23;
        if ($id >= 13259221689642660934 && $id < 13836008077021721634)
            return 24;
        if ($id >= 13836008077021721634 && $id < 14411538489601958181)
            return 25;
        if ($id >= 14411538489601958181 && $id < 14989084168978764931)
            return 26;
        if ($id >= 14989084168978764931 && $id < 15565313755211130708)
            return 27;
        if ($id >= 15565313755211130708 && $id < 16142081929461448429)
            return 28;
        if ($id >= 16142081929461448429 && $id < 16717869929339795988)
            return 29;
        if ($id >= 16717869929339795988 && $id < 17293687793251719004)
            return 30;
        if ($id >= 17293687793251719004 && $id < 17869194657238150155)
            return 31;
        if ($id >= 17869194657238150155 && $id < 18446689638439209978)
            return 32;
        return 32;
    }

}


class ShardLockException extends \Exception {}

class EntityDoesNotExistException extends \Exception {}
