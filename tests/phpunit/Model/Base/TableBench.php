<?php

require_once realpath(__DIR__ . '/../../') . "/FunBenchmark.php";
require_once 'fixtures/SampleOnlyModel.php';

class TableBench extends FunBenchmark {

    public function benchTableReflection() {
        for ($i = 0; $i < $this->N; $i++) {
            SampleOnlyModel::table();
        }

    }

    public function benchTableQuery() {
        $this->N = 100;
        $table = SampleOnlyModel::table();
        for ($i = 0; $i < $this->N; $i++) {
            $table->query(1, 'SELECT * FROM table_sample');
        }
    }

}

TableBench::runBenchmarks();
