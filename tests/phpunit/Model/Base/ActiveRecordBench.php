<?php

require_once realpath(__DIR__ . '/../../') . "/FunBenchmark.php";
require_once 'fixtures/SampleOnlyModel.php';

class ActiveRecordBench extends FunBenchmark {

    private function _prepareDatabaseRows() {
        SampleOnlyModel::create(array(
            'uid' => 2,
            'pid' => 55,
            'title' => 'fooBarPingPong',
        ));
    }

    private function _clearnUp() {
        $table = SampleOnlyModel::table();
        $table->query(1, 'DELETE FROM table_sample');
    }

    public function benchModelInstance() {
        $this->N = 10000;
        for ($i = 0; $i < $this->N; $i++) {
            SampleOnlyModel::instance(array(
                'uid' => 2,
                'pid' => 55,
                'title' => 'fooBarPingPong',
            ), TRUE);
        }
    }

    public function benchManagerInstance() {
        $this->N = 10000;
        for ($i = 0; $i < $this->N; $i++) {
            \Manager\CityManager::getInstance();
        }
    }

    public function benchFindAll() {
        $this->_prepareDatabaseRows();
        $this->N = 1000;
        for ($i = 0; $i < $this->N; $i++) {
            SampleOnlyModel::findAll(4, 'uid>=?', array(2));
        }
        $this->_clearnUp();
    }

    public function benchGet() {
        $this->_prepareDatabaseRows();
        $this->N = 1000;
        for ($i = 0; $i < $this->N; $i++) {
            SampleOnlyModel::get(2, 55);
        }
        $this->_clearnUp();
    }

    public function benchGetAll() {
        $this->_prepareDatabaseRows();
        $this->N = 1000;
        for ($i = 0; $i < $this->N; $i++) {
            SampleOnlyModel::getAll(2);
        }
        $this->_clearnUp();
    }

}

ActiveRecordBench::runBenchmarks();
