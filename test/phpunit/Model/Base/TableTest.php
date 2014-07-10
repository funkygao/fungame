<?php

require_once realpath(__DIR__ . '/../../') . "/FunTestCaseBase.php";
require_once 'fixtures/SampleOnlyModel.php';

class TableTest extends FunTestCaseBase implements \Consts\DbConst {

    protected function tearDown() {
        $table = SampleOnlyModel::table();
        // clear the whole table after each test case
        $table->query(1, 'DELETE FROM table_sample');
    }

    /*
     * Manually create the following table before test runs.
     *
     * CREATE TABLE `table_sample` (
     *      `uid` int(11) NOT NULL DEFAULT '0',
     *      `pid` int(11) NOT NULL DEFAULT '0',
     *      `title` char(50) DEFAULT NULL,
     *      `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     *      `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
     *      `gendar` char(10) DEFAULT NULL,
     *      PRIMARY KEY (`uid`,`pid`)
     * );
     */
    public function testTableBasic() {
        $table = SampleOnlyModel::table();

        // table basic attributes
        $this->assertEquals('table_sample', $table->name);
        $this->assertEquals(self::POOL_USER, $table->pool);
        $this->assertEquals(null, $table->column('uid')->default);
        $this->assertEquals('uid', $table->shardColumn);
        $this->assertInstanceOf('\ReflectionClass', $table->modelClass);
        $this->assertEquals('hello world', $table->column('title')->default);
        $this->assertEquals(array(
                'uid',
                'pid',
            ),
            $table->pk);
        $this->assertInstanceOf('\Model\Base\Column', $table->column('uid'));
        $this->assertEquals('uid', $table->column('uid')->name);
        $this->assertEquals('uid', $table->columns['uid']->name);
        $this->assertEquals(array(
                'uid',
                'pid',
                'title',
                'ctime',
                'mtime',
                'gendar',
                'json',
            ),
            $table->columnNames());
    }

    public function testDataAccessing() {
        // preparation: create 2 rows in db
        $uid1 = 1;
        $uid2 = 2;
        $table = SampleOnlyModel::table();
        $model1 = SampleOnlyModel::create(array(
            'uid' => $uid1,
            'pid' => 78,
            'title' => 'demo of create from Model',
        ));
        $model2 = SampleOnlyModel::create(array(
            'uid' => $uid2,
            'pid' => 92,
            'title' => 'demo2 of create from Model',
        ));

        // table raw sql query
        $rs = $table->query(1, 'SELECT * FROM table_sample');
        $this->assertInstanceOf('\Driver\DbResult', $rs);
        $this->assertEquals(2, $rs->getAffectedRows());
        $this->assertEquals(2, $rs->count());
        $this->assertEquals(2, count($rs)); // DbResult is countable
        $this->assertEquals(0, $rs->getInsertId()); // cause we didn't insert
        $this->assertEquals(1, $rs[0]['uid']);

        // table select query
        $rows = $table->select(1, 'uid>? ORDER BY uid', array(0));
        $this->assertInternalType('array', $rows);
        $this->assertEquals(1, $rows[0]['uid']);
    }

    public function testInsertAndDelete() {
        $this->assertEquals(0, SampleOnlyModel::count(1, 'uid=?', array(4)));
        $table = SampleOnlyModel::table();
        $table->insert(1, array(
            'uid' => 4,
            'pid' => 78,
            'title' => 'demo of create from Model',
        ));
        $this->assertEquals(1, SampleOnlyModel::count(1, 'uid=?', array(4)));

        $table->delete(1, array(
            'uid' => 4,
            'pid' => 78,
            'title' => 'demo of create from Model',
        ));
        $this->assertEquals(0, SampleOnlyModel::count(1, 'uid=?', array(4)));
    }

    public function testUpdate() {
        $this->assertEquals(0, SampleOnlyModel::count(1, 'uid=?', array(4)));
        $table = SampleOnlyModel::table();
        $table->insert(1, array(
            'uid' => 4,
            'pid' => 78,
            'title' => 'demo of create from Model',
        ));
        $this->assertEquals(1, SampleOnlyModel::count(1, 'uid=?', array(4)));

        // change title
        $table->update(1, array('title' => 'haha'),
            'uid=?', array(4));

        // after update, requery from db to check whether update ok
        $rows = $table->select(1, 'uid=?', array(4), 'title');
        $row = $rows[0];
        $this->assertFalse(isset($row['uid'])); // column not included 'uid', only 'title'
        $this->assertEquals('haha', $row['title']);
    }

    public function testInsertWithOnDuplicatedKey() {
        $uid = 1;
        $table = SampleOnlyModel::table();
        $table->insert(1, array(
            'uid' => $uid,
            'pid' => 78,
            'title' => 'demo of create from Model',
        ));
        $this->assertEquals(1, SampleOnlyModel::count($uid, 'uid=?', array($uid)));

        // now the we are to update this row by insert
        $table->insert(1, array(
            'uid' => $uid,
            'pid' => 78,
            'title' => 'demo of create from Model',
        ), FALSE, array(
            'title' => 'bye',
        ));
        $rows = $table->select($uid, 'uid=?', array($uid), 'title');
        $row = $rows[0];
        $this->assertFalse(isset($row['uid'])); // column not included 'uid', only 'title'
        $this->assertEquals('bye', $row['title']);
    }

    public function testColumnChoices() {
        $table = SampleOnlyModel::table();
        $this->assertEquals(array(), $table->columnChoices('non-exist-column'));
        $this->assertEquals(array(), $table->columnChoices('uid'));

        // gendar has defined choices
        $this->assertEquals(array('male', 'female', 'unknown'),
            $table->columnChoices('gendar'));

        // not within choices, will get exception
        $this->setExpectedException('\InvalidArgumentException');
        SampleOnlyModel::instance(array(
            'uid' => 1,
            'pid' => 10,
            'gendar' => 'invalidGendar',
        ));
    }

    public function testUpsert() {
        $this->uid = 2;
        $this->pid = 36;
        $this->title = 'hello world';

        SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
        ));

        $rs = SampleOnlyModel::table()->upsert($this->uid, array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
        ), array('title' => 'shit', 'gendar' => 'mm'));

        $rs = SampleOnlyModel::table()->select($this->uid, 'uid=? AND pid=?',
            array($this->uid, $this->pid));
        $this->assertEquals('shit', $rs[0]['title']);
        $this->assertEquals('mm', $rs[0]['gendar']);
    }

}
