<?php

require_once realpath(__DIR__ . '/../') . "/FunBenchmark.php";

class MQFactoryBench extends FunBenchmark {

    public function benchPublish() {
        // current benchmark result is 166us/op
        $this->N = 10000;
        for ($i = 0; $i < $this->N; $i++) {
            \Driver\MQFactory::instance()->produce('test', array(
                'uid' => 1,
            ));
        }
    }

}

MQFactoryBench::runBenchmarks();
