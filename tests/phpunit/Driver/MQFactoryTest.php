<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class MQFactoryTest extends FunTestCaseBase {

    public function testProduce() {
        $id = \Driver\MQFactory::instance('default')->produce('foo', array(
            'bar' => 12,
        ));
        $this->assertGreaterThanOrEqual(1, $id);
    }

    public function testConsume() {
        list($job, $handler, $params) = \Driver\MQFactory::instance('default')->consume();
        $this->assertEquals('foo', $handler);
        $this->assertEquals(array('bar' => 12), $params);
    }

}

