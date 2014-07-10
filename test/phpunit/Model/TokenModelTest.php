<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class TokenModelTest extends FunTestCaseBase {

    public function testGetInstance() {
        $t1 = \Model\TokenModel::getInstance();
        $t2 = \Model\TokenModel::getInstance();
        $this->assertSame($t1, $t2);
        $this->assertInstanceOf('\Model\TokenModel', $t1);
    }

    public function testAll() {
        $t = \Model\TokenModel::getInstance();
        $uid = 19;
        $token = $t->issueToken($uid, 3600);
        $this->assertEquals(43, strlen($token));
        $this->assertEquals($uid, $t->token2uid($token));
    }

    public function testExpiredToken() {
        $this->markTestSkipped('Only after beta will we validate expiry');

        $token = 'z7Wvdc4ae1MaAAAAAAAAADhF91w3+fokC\/RgFvdFhJQ';
        $this->setExpectedException('\Model\IllegalTokenException');
        $t = \Model\TokenModel::getInstance();
        $t->token2uid($token);
    }

}
