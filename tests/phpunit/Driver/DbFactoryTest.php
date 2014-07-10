<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class DbFactoryTest extends FunTestCaseBase implements \Consts\DbConst {

    public function testBasic() {
        $result = \Driver\DbFactory::instance(self::POOL_USER)
            ->query('UserInfo', 2, 'SELECT * FROM UserInfo WHERE uid=?',
            array(2));
        $this->assertInstanceOf('\Driver\DbResult', $result);
        $row = $result->getResults()[0];
        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals(2, $row['uid']);
    }

    public function testDbFactoryByPool() {
        $result = \Driver\DbFactory::userPool()
            ->query('UserInfo', 2, 'SELECT * FROM UserInfo WHERE uid=?',
                array(2));
        $row = $result[0];
        $this->assertEquals(2, $row['uid']);
    }

    public function testDbFactoryCloseAll() {
        $this->testBasic();
        \Driver\DbFactory::closeAll();
    }
}
