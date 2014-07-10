<?php

require_once realpath(__DIR__ . '/../../') . "/FunTestCaseBase.php";

class FooExtendsBaseManager extends \Manager\Base\BaseManager {

    /**
     * @return FooExtendsBaseManager
     */
    public static function getInstance() {
        return parent::getInstance();
    }

    public function logger() {
        return $this->_getLogger(); // smoke test
    }

}

class BaseManagerTest extends FunTestCaseBase
{

    public function testGetInstanceWithoutArguments() {
        $foo1 = FooExtendsBaseManager::getInstance();
        $foo2 = FooExtendsBaseManager::getInstance();
        $this->assertSame($foo1, $foo2);

        $foo1->logger()->info('test', 'blah');
    }

}
