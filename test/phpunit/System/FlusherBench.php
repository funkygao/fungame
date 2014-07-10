<?php

require_once realpath(__DIR__ . '/../') . "/FunBenchmark.php";

class FlusherBench extends FunBenchmark {

    public function benchGetInstance() {
        for ($i = 0; $i < $this->N; $i++) {
            \System\Flusher::getInstance();
        }
    }

    public function benchReflection() {
        for ($i = 0; $i < $this->N; $i++) {
            new ReflectionClass(__CLASS__);
        }
    }

}

FlusherBench::runBenchmarks();
