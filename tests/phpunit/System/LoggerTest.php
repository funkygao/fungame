<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class LoggerTestHandler implements \System\Appender\Appender {

    public function append($category, $msg) {

    }
}

class LoggerTest extends FunTestCaseBase
{

    protected function setUp() {
        \System\Appender\Factory::register('file:/tmp/');
    }

    public function testGetLogger() {
        $logger = \System\Logger::getLogger(get_class($this));
        $logger = \System\Logger::getLogger();
    }

    public function testFatal() {
        $logger = \System\Logger::getLogger(get_class($this));
        $this->setExpectedException('RuntimeException'); // it will throw exception
        $logger->panic('error', 'hello world');
    }

    public function testInfo() {
        $logger = \System\Logger::getLogger(get_class($this));
        $logger->info('test', array(
            'msg' => 'hello',
            'uid' => 12,
            'misc' => 'foo',
        ));
        $log = $this->lastLineOfFile('/tmp/test');
        $json = explode(' ', $log)[2];
        $msg = json_decode($json, TRUE);
        $this->assertEquals('hello', $msg['msg']);
        $this->assertEquals(12, $msg['uid']);
        $this->assertNotEmpty($msg['_ctx']);
    }

}
