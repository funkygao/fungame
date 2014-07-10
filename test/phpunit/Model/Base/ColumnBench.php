<?php

require_once realpath(__DIR__ . '/../../') . "/FunBenchmark.php";

class ColumnBench extends FunBenchmark implements \Consts\ColumnConst {

    public function benchNewInstance() {
        for ($i = 0; $i < $this->N; $i++) {
            new \Model\Base\Column(array(
                self::NAME => 'uid',
                self::TYPE => self::INTEGER,
                self::DEFAULTS => 1,
            ));
        }
    }

}

ColumnBench::runBenchmarks();
