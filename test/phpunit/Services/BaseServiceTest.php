<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class FooExtendsBaseService extends \Services\Base\BaseService {
    public function verifyInt() {
        foreach (func_get_args() as $arg) {
            $this->_verifyInt($arg);
        }
    }
}

class BarExtendsBaseService extends \Services\Base\BaseService {
}

class BaseServiceTest extends FunTestCaseBase {

    public function testSingleton() {
        $foo = FooExtendsBaseService::getInstance(
            \System\RequestHandler::getInstance(),
            \System\ResponseHandler::getInstance()
        );
        $foo1 = FooExtendsBaseService::getInstance(
            \System\RequestHandler::getInstance(),
            \System\ResponseHandler::getInstance()
        );
        $bar = BarExtendsBaseService::getInstance(
            \System\RequestHandler::getInstance(),
            \System\ResponseHandler::getInstance()
        );
        $this->assertEquals('FooExtendsBaseService', get_class($foo));
        $this->assertEquals('BarExtendsBaseService', get_class($bar));
        $this->assertSame($foo, $foo1);
        $this->assertNotSame($foo, $bar);
    }

    public function testVerifyIntFail() {
        $foo = FooExtendsBaseService::getInstance(
            \System\RequestHandler::getInstance(),
            \System\ResponseHandler::getInstance()
        );
        $this->setExpectedException('\Utils\VerifyInputsException',
            'Invalid argument type: asdf, expected: int');
        $foo->verifyInt(5, 'asdf');
    }

    public function testVerifyIntSuccess() {
        $foo = FooExtendsBaseService::getInstance(
            \System\RequestHandler::getInstance(),
            \System\ResponseHandler::getInstance()
        );
        $foo->verifyInt('12', 8);
        $foo->verifyInt(12, 99, 0, -1);
        $this->assertTrue(TRUE);
    }

}
