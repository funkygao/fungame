<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class FlusherTest extends FunTestCaseBase {

    public function testAll() {
        $f1 = \System\Flusher::getInstance();
        $f2 = \System\Flusher::getInstance();
        $this->assertInstanceOf('\System\Flusher', $f2);
        $this->assertSame($f1, $f2);
    }

}
