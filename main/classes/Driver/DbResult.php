<?php

namespace Driver;

class DbResult implements \ArrayAccess, \IteratorAggregate, \Countable {

    /**
     * @var array
     */
    protected $results = array(); // only for select

    protected $affected_rows = 0; // only for update/delete/insert
    protected $insert_id = 0;

    public function getResults() {
        return $this->results;
    }

    public function setResults(array $data) {
        $this->results = $data;
    }

    public function setAffectedRows($num) {
        $this->affected_rows = $num;
    }

    public function setInsertId($id) {
        $this->insert_id = $id;
    }

    public function getNumRows() {
        return count($this->results);
    }

    public function getAffectedRows() {
        return $this->affected_rows;
    }

    public function getInsertId() {
        return $this->insert_id;
    }

    public function shiftResults() {
        return array_shift($this->results);
    }

    public function offsetExists($offset) {
        return isset($this->results[$offset]);
    }

    public function offsetGet($offset) {
        return $this->results[$offset];
    }

    public function offsetSet($offset, $value) {
        // readonly
    }

    public function offsetUnset($offset) {
        // readonly
    }

    public function count() {
        return $this->getNumRows();
    }

    public function getIterator() {
        $result = new \ArrayObject($this->results);
        return $result->getIterator();
    }

}
