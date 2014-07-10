<?php

// Detecting slow tests with PHPUnit
class PHPUnitTestListener implements PHPUnit_Framework_TestListener {
    private $_time;
    private $_slowThreshold = 0;

    public function startTest(PHPUnit_Framework_Test $test) {
        $this->_time = time();
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {
        $current = time();
        $elapsed = $current - $this->_time;
        if($elapsed > $this->_slowThreshold ) {
            echo "\nName: "
                . $test->getName()
                . " took " . $elapsed
                . " second(s) (from: $this->_time, to: $current)\n";
        }
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time){
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

}
