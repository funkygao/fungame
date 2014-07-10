<?php

namespace Utils;

/**
 * Helper class for building sql SELECT statements progmatically.
 * Currently ONLY for building 'select' query.
 *
 * JOIN NOT supported!
 */
final class SqlBuilder {

    private $table; // table name

    private $select = '*'; // select fields
    private $order;
    private $limit;
    private $offset;
    private $group;
    private $having;
    private $where; // e,g. pid in (?) and age>?

    public function __construct($table) {
        $this->table = $table;
    }

    public function __toString() {
        return $this->to_s();
    }

    public function tableName() {
        return $this->table;
    }

    /**
     * @param string $where Where clause, bind values not included
     * @return SqlBuilder
     */
    public function where($where) {
        $this->where = $where;
        return $this;
    }

    public function andWhere($where) {
        if (!$this->where) {
            $this->where = $where;
        } else {
            $this->where .= " AND $where";
        }

        return $this;
    }

    /**
     * @param string $order
     * @return SqlBuilder
     */
    public function orderBy($order) {
        $this->order = $order;
        return $this;
    }

    /**
     * @param string $group
     * @return SqlBuilder
     */
    public function groupBy($group) {
        $this->group = $group;
        return $this;
    }

    /**
     * @param string $having
     * @return SqlBuilder
     */
    public function having($having) {
        $this->having = $having;
        return $this;
    }

    /**
     * @param int $limit
     * @return SqlBuilder
     */
    public function limit($limit) {
        $this->limit = intval($limit);
        return $this;
    }

    /**
     * @param int $offset
     * @return SqlBuilder
     */
    public function offset($offset) {
        $this->offset = intval($offset);
        return $this;
    }

    /**
     * @param string $select Select fields. i,e select('uid, name, title')
     * @return SqlBuilder
     */
    public function select($select = '*') {
        $this->select = $select;
        return $this;
    }

    /**
     * Returns the SQL string.
     *
     * @see __toString
     * @return string Sql statement without binded values
     */
    public function to_s() {
        $sql = "SELECT $this->select FROM $this->table";

        if ($this->where) {
            $sql .= " WHERE $this->where";
        }

        if ($this->group) {
            $sql .= " GROUP BY $this->group";
        }

        if ($this->having) {
            $sql .= " HAVING $this->having";
        }

        if ($this->order) {
            $sql .= " ORDER BY $this->order";
        }

        if ($this->limit || $this->offset) {
            $offset = is_null($this->offset) ? '' : $this->offset . ',';
            $sql .= " LIMIT {$offset}$this->limit";
        }

        return $sql;
    }

}

class SqlInvalidException extends \Exception {

}