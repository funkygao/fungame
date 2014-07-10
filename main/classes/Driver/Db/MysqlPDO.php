<?php

namespace Driver\Db;

class MysqlPDO extends \PDO {
    protected $txnCounter = 0;

    public function beginTransaction() {
        if (!$this->txnCounter++) {
            return parent::beginTransaction();
        }

        return $this->txnCounter >= 0;
    }

    public function commit() {
        if (!--$this->txnCounter) {
            return parent::commit();
        }

        return $this->txnCounter >= 0;
    }

    public function rollback() {
        if ($this->txnCounter >= 0) {
            $this->txnCounter = 0;
            return parent::rollback();
        }

        $this->txnCounter = 0;
        return false;
    }

}
