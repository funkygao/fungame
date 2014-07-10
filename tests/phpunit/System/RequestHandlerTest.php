<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class RequestHandlerTest extends FunTestCaseBase {

    public function testSingleton() {
        $req1 = \System\RequestHandler::getInstance();
        $req2 = \System\RequestHandler::getInstance();
        $this->assertEquals($req1, $req2);
        $this->assertSame($req1, $req2);
    }

}
