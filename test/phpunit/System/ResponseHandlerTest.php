<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

/**
 * TODO If you run 'phpunit ResponseHandlerTest', it will fail, why?
 * PHPUnit fears singleon.
 */
class ResponseHandlerTest extends FunTestCaseBase {

    /**
     * @var \System\ResponseHandler
     */
    private $_response;

    protected function setUp() {
        parent::setUp();

        $this->_response = \System\ResponseHandler::getInstance();
    }

    /**
     * Yes, PHPUnit support annotation.
     *
     * @see http://phpunit.de/manual/3.6/en/appendixes.annotations.html
     *
     * @outputBuffering enabled
     */
    public function testPrintResponseFail() {
        $this->_response->fail()->setMessage('oh no')->setPayload(array('a' => 'b'))->printResponse();
        $this->assertEquals('{"a":"b","ok":0,"msg":"oh no"}', ob_get_contents());
    }

    public function testPrintResponseOk() {
        // when succeed, message won't be rendered to client
        $this->_response->succeed()->setMessage('shit')->printResponse();
        $this->assertEquals('{"ok":1}', ob_get_contents());
    }

    public function testPrintResponseMaintain() {
        $this->_response->underMaintenance()->setMessage('shit')->printResponse();
        $this->assertEquals('{"ok":2,"msg":"shit"}', ob_get_contents());
    }

    public function testPrintResponseCheatDetected() {
        $this->_response->cheatDetected()->setMessage('you are cheater')->printResponse();
        $this->assertEquals('{"ok":3,"msg":"you are cheater"}', ob_get_contents());
    }

}
