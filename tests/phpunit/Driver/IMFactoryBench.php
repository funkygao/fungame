<?php

require_once realpath(__DIR__ . '/../') . "/FunBenchmark.php";

class IMFactoryBench extends FunBenchmark {

    public function benchPublish() {
        $this->N = 50;
        for ($i = 0; $i < $this->N; $i++) {
            \Driver\IMFactory::instance()->publish('test', 'test', array(
                'uid' => 1,
            ));
        }

    }

}

IMFactoryBench::runBenchmarks();
