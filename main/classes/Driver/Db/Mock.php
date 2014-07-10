<?php

namespace Driver\Db;

/**
 * Database Mocker for unit test.
 *
 * @package Driver\Db
 */
final class Mock implements Driver {

    public $rows = array();

    public function query($table, $hintId, $sql, array $args = array()) {
        $parser = new \PHPSQLParser($sql, true);
        $parsed = $parser->parsed;
        if (!empty($parsed['SELECT'])) {
            $i = 0;
            $results = array();
            foreach ($parsed['WHERE'] as $where) {
                if ($where['expr_type'] == 'colref') {
                    if ($where['base_expr'] != '?' && $where['base_expr'] != '=') {
                        $first = $where['base_expr'];
                    }

                    if ($where['base_expr'] == '?') {
                        $key = $first . ':' . $args[$i];
                        if (!empty($this->rows[$table][$key])) {
                            $results[] = $this->rows[$table][$key];
                        }

                        $i ++;
                        continue;
                    }
                }
            }

            return $results;
        } elseif (!empty($parsed['INSERT'])) {
            $i = 0;
            $row = array();
            $cols = array();
            foreach ($parsed['INSERT'][0]['columns'] as $column) {
                $row[$column['base_expr']] = $args[$i];
                $cols[] = $column['base_expr'];
                $i ++;
            }

            $i = 0;
            foreach ($cols as $col) {
                $this->rows[$table][$col . ':' . $args[$i]] = $row;
                $i ++;
            }

        } elseif (!empty($parsed['UPDATE'])) {

        } elseif (!empty($parsed['DELETE'])) {

        }
    }

    public function close() {

    }

}
