<?php
/**
 * Uncover the performance of php class functions.
 */

require_once "FunBenchmark.php";

class PhpBenchFoo {
    public static function getCalledClass() {
        return get_called_class();
    }
}

class PhpBenchFooChild extends PhpBenchFoo { }
class PhpBenchFooChild2 extends PhpBenchFooChild { }

class PhpBench extends FunBenchmark {

    public function benchGetCalledClass() {
        self::assertEquals('PhpBenchFooChild', PhpBenchFooChild::getCalledClass());
        for ($i = 0; $i < $this->N; $i++) {
            PhpBenchFooChild::getCalledClass();
        }
    }

    public function benchGetCalledClass2() {
        self::assertEquals('PhpBenchFooChild2', PhpBenchFooChild2::getCalledClass());
        for ($i = 0; $i < $this->N; $i++) {
            PhpBenchFooChild2::getCalledClass();
        }
    }

    public function benchDebugBacktrace() {
        for ($i = 0; $i < $this->N; $i++) {
            debug_backtrace();
        }
    }

}

PhpBench::runBenchmarks();
