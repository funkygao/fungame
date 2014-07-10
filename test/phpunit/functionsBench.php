<?php

require_once "FunBenchmark.php";

class functionsBench extends FunBenchmark {

    public function benchStrStartswith() {
        for ($i = 0; $i < $this->N; $i++) {
            str_startswith('abcdef', 'ab');
        }
    }

    public function benchStrEndswith() {
        for ($i = 0; $i < $this->N; $i++) {
            str_endswith('abcdef', 'ab');
        }
    }

    public function benchStrContains() {
        for ($i = 0; $i < $this->N; $i++) {
            str_contains('abcdef', 'ab');
        }
    }

    public function benchIsBetween() {
        for ($i = 0; $i < $this->N; $i++) {
            in_between(5, 10, 6);
        }
    }

    public function benchArrayDeepGet() {
        $arr = array(
            'model' => array(
                'user' => array(
                    'uid' => 5
                )
            )
        );
        $this->assertEquals(5, array_deep_get($arr, 'model.user.uid'));

        for ($i = 0; $i < $this->N; $i++) {
            array_deep_get($arr, 'model.user.uid');
        }
    }

    public function benchRequestCtx() {
        for ($i = 0; $i < $this->N; $i++) {
            request_ctx();
        }
    }

}

functionsBench::runBenchmarks();
