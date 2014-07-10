<?php

require_once realpath(__DIR__ . "/../..") . '/main/init.php';

/**
 * All benchmark file ends with 'Bench', e,g. ModelBench.php
 * All benchmark class ends with 'Bench', e,g. ModelBench
 * All benchmark method starts with 'bench', e.g. benchGetCalledClass()
 *
 * For more sample benchmark cases, seee functionsBench.php
 */
class FunBenchmark {

    const BENCHMARK_METHOD_PREFIX = 'bench';
    const LOOPS = 1000000; // 1 Million

    public $N = self::LOOPS;

    protected function _baseLoopCost() {
        $t1 = microtime(TRUE);
        for ($i = 0; $i < $this->N; $i++) { }
        return microtime(TRUE) - $t1;
    }

    public final function run() {
        $testCase = get_called_class();
        echo $testCase . PHP_EOL;

        $relection = new ReflectionClass($testCase);
        foreach ($relection->getMethods(ReflectionMethod::IS_PUBLIC) as $testMethod) {
            if (str_startswith($testMethod->getName(), self::BENCHMARK_METHOD_PREFIX)) {
                $t1 = microtime(TRUE);
                $m1 = memory_get_usage(TRUE);
                $memPeak1 = memory_get_peak_usage(TRUE); // phpunit本身的内存占用

                // call the benchmark method
                $testMethod->invoke($this);

                $elapsed = microtime(TRUE) - $t1; // in seconds
                $memUsage = memory_get_usage(TRUE) - $m1;
                $memPeak = memory_get_peak_usage(TRUE) - $memPeak1;

                echo sprintf("%30s %14.3fms %8dByte %14.3f us/op %s@peak\n",
                    $testMethod->getName(),
                    $elapsed * 1000,
                    $memUsage,
                    ($elapsed - $this->_baseLoopCost()) * 1000 * 1000/$this->N,
                    self::humanized_bytes($memPeak)
                );

                // reset loops count
                $this->N = self::LOOPS;
            }
        }
    }

    public final function assertEquals($expected, $got) {
        if ($expected != $got) {
            echo "Expected $expected, but got $got\n";
            exit;
        }
    }

    public static final function runBenchmarks() {
        $cls = get_called_class();
        $bench = new $cls();
        $bench->run();
    }

    public static function humanized_bytes($size, $unit = '') {
        if ((!$unit && $size >= 1 << 30) || $unit == "GB") {
            return number_format($size / (1 << 30), 2) . "GB";
        }
        if ((!$unit && $size >= 1 << 20) || $unit == "MB") {
            return number_format($size / (1 << 20), 2) . "MB";
        }
        if ((!$unit && $size >= 1 << 10) || $unit == "KB") {
            return number_format($size / (1 << 10), 2) . "KB";
        }

        return number_format($size) . "Byte";
    }

}
