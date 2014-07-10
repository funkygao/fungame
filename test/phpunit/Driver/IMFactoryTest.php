<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class IMFactoryTest extends FunTestCaseBase {

    public function testPublish() {
        $result = \Driver\IMFactory::instance()->publish('test', 'test', array(
            'uid' => 1,
        ));
        $this->assertTrue($result);
    }

}

