<?php

namespace Model\Base;

/**
 * Table is NOT cache aware!
 *
 * Use Table CRUD with care! We prefer to use Model.
 */
final class Table implements \Consts\ColumnConst {

    /**
     * Reflection class of {@link Model}.
     *
     * @var \ReflectionClass
     */
    public $modelClass;

    /**
     *
     * @var Column[] {column_name: Column_object}
     */
    public $columns;

    /**
     * Table name.
     *
     * @var string
     */
    public $name;

    /**
     * DB pool name.
     *
     * @var string
     */
    public $pool;

    /**
     * @var string
     */
    public $ticket;

    /**
     * The SINGLE shard column name.
     *
     * @var string
     */
    public $shardColumn;

    /**
     * List of primary key names.
     *
     * Can identify a row in a table. It's array because it can be compound primary key.
     *
     * @var array Even if the table has 1 primary key, it is array
     */
    public $pk = array();

    /**
     * @param string $modelClassName
     * @return Table
     */
    public static function load($modelClassName) {
        static $tables = array();
        if (!isset($tables[$modelClassName])) {
            $tables[$modelClassName] = new self($modelClassName);
        }

        return $tables[$modelClassName];
    }

    private function __construct($modelClassName) {
        $this->modelClass = new \ReflectionClass($modelClassName);
        if (($pool = $this->modelClass->getStaticPropertyValue('pool', NULL))) {
            $this->pool = $pool;
        } else {
            throw new \InvalidArgumentException('empty pool declaration');
        }
        if (($table = $this->modelClass->getStaticPropertyValue('table', NULL))) {
            $this->name = $table;
        } else {
            throw new \InvalidArgumentException('empty table declaration');
        }
        if (($ticket = $this->modelClass->getStaticPropertyValue('ticket', NULL))) {
            $this->ticket = $ticket;
        } else {
            $this->ticket = $this->name;
        }
        $columns = $this->modelClass->getStaticPropertyValue('columns', NULL);
        if (is_null($columns)) {
            throw new \InvalidArgumentException('emptyt columns declaration');
        }

        $this->columns = array();
        foreach ($columns as $attributes) {
            $column = new Column($attributes);
            $this->columns[$column->name] = $column;

            // shard column
            if ($column->shard) {
                if (!is_null($this->shardColumn)) {
                    throw new \InvalidArgumentException('More than one column declared as shard');
                } else {
                    $this->shardColumn = $column->name;
                }
            }

            // pk
            if ($column->pk) {
                if (is_null($this->pk)) {
                    $this->pk = array($column->name);
                } else {
                    $this->pk[] = $column->name;
                }
            }
        }
    }

    public function validateRow(array $row) {
        // TODO
    }

    /**
     * Get all the column names of this table.
     *
     * @return array [column_name, ...]
     */
    public function columnNames() {
        return array_keys($this->columns);
    }

    /**
     * Get a column object by column name.
     *
     * @param string $column Column name
     * @return Column
     */
    public function column($column) {
        return $this->columns[$column];
    }

    /**
     * Get alternatives of a column value.
     *
     * @param string $column Column name
     * @return array
     */
    public function columnChoices($column) {
        if (!array_key_exists($column, $this->columns)
            || !is_array($this->columns[$column]->choices)) {
            return array();
        }

        return $this->columns[$column]->choices;
    }

    /**
     * Query DB by raw sql.
     *
     * @param int $hintId
     * @param string $sql
     * @param array $args
     * @return \Driver\DbResult
     */
    public function query($hintId, $sql, array $args = array()) {
        return \Driver\DbFactory::instance($this->pool)
            ->query($this->name, $hintId, $sql, $args);
    }

    /**
     * Global query.
     *
     * Same as query except that DB is not sharded.
     *
     * @param string $sql
     * @param array $args
     * @return \Driver\DbResult
     */
    public function g_query($sql, array $args = array()) {
        return \Driver\DbFactory::instance($this->pool)
            ->query($this->name, 0, $sql, $args);
    }

    /**
     * @param int $hintId
     * @param string $whereClause e,g. 'uid=? AND pid>?'
     * @param array $args e,g. array(5, 12)
     * @param string $columns e,g. 'uid,pid,ctime,mtime'
     * @return array List of db row
     */
    public function select($hintId, $whereClause, array $args = array(), $columns = '*') {
        $sql = "SELECT $columns FROM {$this->name}";
        if (trim($whereClause)) {
            $sql .= " WHERE $whereClause";
        }
        return $this->query($hintId, $sql, $args)->getResults();
    }

    /**
     * @param int $hintId
     * @param array $row
     * @param bool $ignore
     * @param array $onDuplicateKeyUpdate Imitation of upsert.
     * @return \Driver\DbResult
     */
    public function insert($hintId, array $row, $ignore = FALSE, $onDuplicateKeyUpdate = array()) {
        $sql = "INSERT INTO {$this->name}";
        if ($ignore) {
            $sql = "INSERT IGNORE INTO {$this->name}";
        }

        // setup the default ctime if not specified
        if (isset($this->columns[self::CTIME]) && empty($row[self::CTIME])) {
            $row[self::CTIME] = \System\RequestHandler::getInstance()->currentOpTime();
        }

        list($cols, $vals, $args) = $this->_getBind($row);
        $sql .= "(" . join(",", $cols) . ") VALUES(" . join(",", $vals) . ")";
        if ($onDuplicateKeyUpdate) {
            if (!is_array($onDuplicateKeyUpdate)) {
                $dupStr = $onDuplicateKeyUpdate;
            } else {
                list($cols, $vals, $dupArgs) = $this->_getBind($onDuplicateKeyUpdate);
                $toJoin = array();
                foreach ($cols as $pos => $col) {
                    $toJoin[] = $col . '=' . $vals[$pos];
                }

                $dupStr = implode(",", $toJoin);
                $args = array_merge($args, $dupArgs);
            }

            $sql .= " ON DUPLICATE KEY UPDATE $dupStr";
        }

        return \Driver\DbFactory::instance($this->pool)
            ->query($this->name, $hintId, $sql, $args);
    }

    /**
     * Insert if not exist, else update, in an atomic way.
     *
     * @param int $hintId
     * @param array $row
     * @param array $onDuplicateKeyUpdate e,g. array('value' => 'value+2')
     * @return \Driver\DbResult
     */
    public final function upsert($hintId, array $row, array $onDuplicateKeyUpdate) {
        return $this->insert($hintId, $row, FALSE, $onDuplicateKeyUpdate);
    }

    public function replace($hintId, array $keyValues) {
        list($cols, $vals, $args) = $this->_getBind($keyValues);
        $sql = "REPLACE INTO {$this->name} (" . join(",", $cols) . ") VALUES(" . join(",", $vals) . ")";
        return \Driver\DbFactory::instance($this->pool)
            ->query($this->name, $hintId, $sql, $args);
    }

    public function update($hintId, array $set, $whereClause, array $whereArgs = array()) {
        list($cols, $vals, $args) = $this->_getBind($set);
        if ($whereArgs) {
            $args = array_merge($args, $whereArgs);
        }

        $setInfo = array();
        foreach ($cols as $pos => $bind) {
            $setInfo[] = "$bind=" . $vals[$pos];
        }

        $setStr = join(",", $setInfo);
        if (!$setStr) {
            throw new \Exception("There is no setStr this will produce a Syntax Error\n");
        }

        $sql = "UPDATE {$this->name} SET " . $setStr . " WHERE $whereClause";
        return \Driver\DbFactory::instance($this->pool)
            ->query($this->name, $hintId, $sql, $args);
    }

    public function delete($hintId, array $keyValues) {
        $sql = "DELETE FROM {$this->name} WHERE 1=1";
        foreach ($keyValues as $key => $_) {
            $sql .= " AND $key=?";
        }
        return \Driver\DbFactory::instance($this->pool)
            ->query($this->name, $hintId, $sql, array_values($keyValues));
    }

    private function _getBind(array &$keyValues) {
        $cols = $args = $value = array();

        foreach ($keyValues as $col => $value) {
            if ($this->columns[$col]->type == self::DATETIME) {
                $value = ts_unix2mysql($value);
            }
            if ($this->columns[$col]->type == self::JSON) {
                $value = json_encode($value);
            }

            $cols[] = $col;

            if (is_null($value)) {
                $vals[] = '\N'; // NULL in mysql
            } else if ($value === "NOW()") {
                $vals[] = 'NOW()';
            } else {
                $vals[] = '?';
                $args[] = $value;
            }
        }

        return array($cols, $vals, $args);
    }

}
