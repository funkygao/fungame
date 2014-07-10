<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class ApplicationTest extends FunTestCaseBase {

    public function testBuildController() {
        $app = System\Application::getInstance(System\RequestHandler::getInstance(),
            System\ResponseHandler::getInstance());
        $controller = $app->buildController('Call');
        $this->assertInstanceOf('\Service\CallService', $controller);
    }

}
