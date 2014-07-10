<?php

require_once realpath(__DIR__ . '/../../') . "/FunTestCaseBase.php";

class ColumnTest extends FunTestCaseBase implements \Consts\ColumnConst {

    public function testAll() {
        $column = new \Model\Base\Column(array(
            self::NAME     => 'uid',
            self::TYPE     => self::INTEGER,
            self::SHARD    => FALSE,
            self::PK       => TRUE,
            self::NULLABLE => FALSE,
        ));
        $this->assertEquals(NULL, $column->default);
        $this->assertTrue($column->pk);
        $this->assertFalse($column->shard);
        $this->assertFalse($column->nullable);
        $this->assertEquals(self::INTEGER, $column->type);
        $this->assertEquals('uid', $column->name);

        $this->assertEquals(12, $column->cast(12));
        $this->setExpectedException('\InvalidArgumentException');
        $column->cast('thisIsStringInsteadOfInt');
    }

}
