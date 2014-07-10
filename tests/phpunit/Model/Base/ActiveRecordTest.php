<?php

require_once realpath(__DIR__ . '/../../') . "/FunTestCaseBase.php";
require_once 'fixtures/SampleOnlyModel.php';
require_once 'fixtures/UserInfoModel.php';
require_once 'fixtures/UserLookupModel.php';

class ActiveRecordTest extends FunTestCaseBase implements \Consts\JobConst {

    // this row will always exists in db before each test case
    private $uid = 2;
    private $pid = 36;
    private $title = 'hello world';

    protected function setUp() {
        parent::setUp();

        SampleOnlyModel::table()->query(1, 'CREATE TABLE IF NOT EXISTS table_sample(uid int, pid int, title char(100), gendar char(50), ctime timestamp, mtime timestamp, json blob, primary key(uid, pid))');
        \Model\JobModel::table()->query(2, 'DELETE FROM Job');
    }

    protected function tearDown() {
        parent::tearDown();

        $table = SampleOnlyModel::table();
        SampleOnlyModel::tearDownForUnitTest();
        \Model\CityModel::tearDownForUnitTest();
        // clear the whole table after each test case
        $table->query(1, 'DELETE FROM table_sample');
        $table->query(1, 'DELETE FROM UserInfo');
        $table->query(2, 'DELETE FROM UserCity');
    }

    private function prepareSampleRow() {
        // create a row
        SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
        ));
    }

    public function testModelCreateWithoutHintId() {
        $this->setExpectedException('\InvalidArgumentException');
        SampleOnlyModel::instance(
            array(
                'pid' => 36,
                'title' => 'hello world',
            )
        );
    }

    public function testModelObjectOrientedWay() {
        $this->assertEquals(0, count(\System\Flusher::getInstance()->getSubscribers()));
        $s1 = SampleOnlyModel::instance(
            array(
                'uid' => 1,
                'pid' => 36,
                'title' => 'hello world',
            )
        );
        $s2 = SampleOnlyModel::instance(
            array(
                'uid' => 1,
                'pid' => 36,
                'title' => 'hello world',
            )
        );
        $s3 = SampleOnlyModel::instance(
            array(
                'uid' => 2,
                'pid' => 36,
                'title' => 'hello world',
            )
        );

        $this->assertTrue($s2->equals($s1));
        $this->assertFalse($s2->equals($s3));
        $this->assertTrue(isset($s1->title));
        $this->assertFalse(isset($s1->non_exist_column));
        $this->assertSame($s2, $s1); // same data will be singleton
        $this->assertTrue($s1->isDirty());
        // Flusher subscribers should be 2 instead of 3 cause s1 is s2
        $this->assertEquals(2, count(\System\Flusher::getInstance()->getSubscribers()));
        $this->assertNotSame($s1, $s3);
        $this->assertEquals(array(
            'uid' => 1,
            'pid' => 36,
        ), $s2->pkValues());
        $this->assertEquals('hello world', $s2->title);
        $this->assertEquals(1, $s2->hintId());

        // export
        $this->assertTrue(is_array($s1->export()));
        $this->assertInternalType('array', $s1->export());
        // export exclude some columns
        $exported = $s1->export(array('title', 'mtime', 'gendar'));
        $this->assertFalse(isset($exported['title']));
        $this->assertFalse(isset($exported['gendar']));
        $this->assertFalse(array_key_exists('mtime', $exported));
        $this->assertTrue(isset($exported['uid']));
        $this->assertEquals(36, $exported['pid']);
        $this->assertTrue(is_numeric($exported['ctime'])); // auto convert from mysql timestamp
        // 1401605156 is 2014-06-01 14:46
        $this->assertGreaterThan(1401605156, $exported['ctime']);
        // export data is readonly
        $row = $s1->export();
        $row['uid'] = 1988;
        $this->assertNotEquals($row['uid'], $s1->uid);
        $this->assertEquals(1, $s1->uid);

        // default column value TODO
        //$this->assertEquals('unkown', $s->gendar);
    }

    public function testCount() {
        $this->prepareSampleRow();
        $this->assertEquals(1, SampleOnlyModel::count($this->uid, 'uid=?', array($this->uid)));

        // non exist
        $this->assertEquals(0, SampleOnlyModel::count(1, 'uid=?', array(1)));
    }

    public function testDelete() {
        $model = SampleOnlyModel::create(array(
            'uid' => 2,
            'pid' => 23,
            'title' => 'blah',
        ));
        $this->assertEquals('blah', $model->title);
        $this->assertFalse($model->isDirty());

        // confirm that the model was written to db
        $this->assertEquals(true, SampleOnlyModel::exists(2,
            'uid=? AND pid=?',
            array(2, 23)));
        $model->delete();
    }

    public function testNextHintId() {
        $uid1 = UserInfoModel::nextHintId();
        $this->assertGreaterThan(0, $uid1);
        $uid2 = UserInfoModel::nextHintId();
        $this->assertEquals($uid1 + 1, $uid2);
        $this->assertInternalType('int', $uid2);
    }

    public function testSqlBuilder() {
        $sql = SampleOnlyModel::sqlBuilder();
        $this->assertEquals('table_sample', $sql->tableName());
    }

    public function testCreate() {
        $model = SampleOnlyModel::create(array(
            'uid' => 2,
            'pid' => 78,
            'title' => 'demo of create from Model',
        ));
        $this->assertInstanceOf('\Model\Base\ActiveRecord', $model);
        $this->assertEquals(2, $model->uid);
        $this->assertEquals(array(
                'uid' => 2,
                'pid' => 78),
            $model->pkValues());
        $this->assertEquals('demo of create from Model', $model->title);

        // delete it, so that we can run this many times
        $model->delete();
    }

    public function testExists() {
        $this->prepareSampleRow();
        $this->assertEquals(true, SampleOnlyModel::exists($this->uid,
            'uid=? and pid=?', array($this->uid, $this->pid)));
    }

    public function testModelFindAll() {
        $this->prepareSampleRow();
        $models = SampleOnlyModel::findAll($this->uid, 'uid>=?', array($this->uid));
        foreach ($models as $model) {
            $this->assertInstanceOf('\Model\Base\ActiveRecord', $model);
            $this->assertGreaterThanOrEqual($this->uid, $model->uid);
        }
    }

    public function testFindByTitle() {
        $this->prepareSampleRow();
        $models = SampleOnlyModel::findAll($this->uid,
            'title=:title',
            array(':title' => 'hello world')
        );
        $this->assertNotEmpty($models);
        $this->assertEquals($this->uid, $models[0]->uid);
    }

    public function testFindAllUsingIn() {
        $this->markTestIncomplete(); // cann't use this, mysql got: SELECT * FROM table_sample WHERE uid in ('2,1')
        $this->prepareSampleRow();
        $models = SampleOnlyModel::findAll($this->uid,
            'uid in (:uids)',
            array(':uids' => join(',', array($this->uid, 1)))
        );
        $this->assertNotEmpty($models);
        $this->assertEquals($this->uid, $models[0]->uid);
    }

    public function testModelGetAndGetAll() {
        $this->prepareSampleRow();
        $model = SampleOnlyModel::get($this->uid, $this->pid); // uid, pid
        $this->assertEquals(36, $model->pid);
        $model->gendar = "female";
        $this->assertTrue($model->isDirty());
        $this->assertTrue($model->save());

        $nonExistModel = SampleOnlyModel::get($this->uid, 9876);
        $this->assertNull($nonExistModel);

        // getAll is for partial compound pk
        $models = SampleOnlyModel::getAll($this->uid);
        foreach ($models as $model) {
            $this->assertEquals($this->uid, $model->uid);
        }
    }

    public function testGetThenGetAll() {
        $jobId1 = \Model\JobModel::nextHintId();
        $jobId2 = \Model\JobModel::nextHintId();
        $uid = 1;
        $cityId = 1;
        $row = array(
            'uid' => $uid,
            'city_id' => $cityId,
            'job_id' => $jobId1,
            'event_type' => self::EVENT_TYPE_PVP_MARCH,
            'time_start' => time(),
            'time_end' => time() + 10,
            'trace' => array('x' => 'y'),
        );
        \Model\JobModel::table()->insert($uid, $row);
        $row['job_id'] = $jobId2;
        \Model\JobModel::table()->insert($uid, $row);

        \Model\JobModel::get($uid, $jobId1);
        $models = \Model\JobModel::getAll($uid);
        $this->assertEquals(2, count($models));
    }

    public function testGetThenExportAll() {
        $jobId1 = \Model\JobModel::nextHintId();
        $jobId2 = \Model\JobModel::nextHintId();
        $uid = 1;
        $cityId = 1;
        $row = array(
            'uid' => $uid,
            'city_id' => $cityId,
            'job_id' => $jobId1,
            'event_type' => self::EVENT_TYPE_PVP_MARCH,
            'time_start' => time(),
            'time_end' => time() + 10,
            'trace' => array('x' => 'y'),
        );
        \Model\JobModel::table()->insert($uid, $row);
        $row['job_id'] = $jobId2;
        \Model\JobModel::table()->insert($uid, $row);

        \Model\JobModel::get($uid, $jobId1);
        $rows = \Model\JobModel::exportAll($uid);
        $this->assertEquals(2, count($rows));
        $this->assertEquals(1, count($rows[0]['uid']));
    }

    public function testSaveNewModel() {
        $model = UserInfoModel::instance(array(
            'uid' => $this->uid,
            'alliance_id' => 0,
            'power' => 101,
            'gold' => 0,
            'name' => 'testaccount',
            'inventory_slots' => 4,
            'create_date' => 'NOW()',
            'extra' => '',
            'nonExistColumn' => 'hello', // will not be attribute of the model
        ));
        $this->assertTrue($model->save());
        $this->assertEquals($this->uid, $model->uid);
        $model->delete();

        $this->setExpectedException('\InvalidArgumentException');
        $model->nonExistColumn; // will trigger exception
    }

    public function testModelClone() {
        $this->markTestSkipped();
    }

    public function testNonShardModel() {
        $user = UserLookupModel::get(1);
        $this->assertEquals(1, $user->entityId);
        $this->assertEquals(1, $user->shardId);
        $this->assertEquals('unittest', $user->deviceUUID);
    }

    public function testGetOfModelsCache() {
        $this->prepareSampleRow();
        $model = SampleOnlyModel::get($this->uid, $this->pid); // uid, pid
        $this->assertEquals($this->pid, $model->pid);
        $model1 = SampleOnlyModel::get($this->uid, $this->pid);
        $this->assertTrue($model->equals($model1));
        $this->assertSame($model, $model1);

        $models1 = SampleOnlyModel::getAll($this->uid);
        $models2 = SampleOnlyModel::getAll($this->uid);
        $this->assertSame($models1[0], $models2[0]);
        // getAll() will fill cache for get()
        $this->assertSame($models1[0], SampleOnlyModel::get($this->uid, $this->pid));
    }

    public function testModelLoad() {
        $nonExistentUid = 908;
        $this->setExpectedException('Model\Base\ObjectNotFoundException');
        UserLookupModel::load($nonExistentUid);
    }

    public function testColumnChoices() {
        $this->setExpectedException('\InvalidArgumentException');
        SampleOnlyModel::instance(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'invalid gendar', // should throw exception
        ));
    }

    public function testTransientProperty() {
        $model = SampleOnlyModel::instance(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female', // should throw exception
        ));
        $this->assertInstanceOf('SampleOnlyModel', $model);

        $city = \Model\CityModel::instance(array(
            'uid' => $this->uid,
            'city_id' => 1,
            'name' => '',
        ));

        $model->city = $city;
        $this->assertTrue(isset($model->city));
        $this->assertFalse(isset($model->invalid_property));
        $this->assertEquals($city, $model->city);
    }

    public function testJsonColumn() {
        $model = SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female',
            'json' => array(
                'x' => 12,
                'y' => 45,
            ),
        ));
        $this->assertEquals(12, $model->json['x']);
        $this->assertEquals(45, $model->json['y']);

        // can't directly modify json content, will get error:
        // Indirect modification of overloaded property SampleOnlyModel::$json has no effect
        // $model->json['x'] = 99;

        // when you want to modify the json content, you must do like this
        $json = $model->json;
        $json['x'] = 13; // 13 != 12
        $model->json = $json;
        $this->assertTrue($model->isDirty());

        // we should pass in array instead of marshalled string to ActiveRecord JSON column
        $this->setExpectedException('\InvalidArgumentException');
        SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid + 1,
            'title' => $this->title,
            'gendar' => 'female',
            'json' => json_encode(array(
                'x' => 12,
                'y' => 45,
            )),
        ));
    }

    public function testCtimeAndMtime() {
        $model = SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female',
        ));
        $this->assertTrue(is_numeric($model->ctime));
        $this->assertTrue(is_numeric($model->mtime));
        $this->assertGreaterThan(1401694203, $model->ctime);

        // TODO auto update mtime, but not update ctime
    }

    public function testDeleteThenGetAll() {
        $model = SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female',
        ));
        $this->assertNotNull($model);
        $this->assertEquals(1, count(SampleOnlyModel::getAll($this->uid)));

        $model->delete();
        $this->assertTrue($model->isDeleted());
        $deletedModel = SampleOnlyModel::get($this->uid, $this->pid);
        $this->assertNull($deletedModel);

        $this->assertEquals(0, count(SampleOnlyModel::getAll($this->uid)));
    }

    public function t1estDeleteThenExportAll() {
        $model = SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female',
        ));
        $this->assertEquals(1, count(SampleOnlyModel::exportAll($this->uid)));

        $model->delete();
        $this->assertTrue($model->isDeleted());
        $deletedModel = SampleOnlyModel::get($this->uid, $this->pid);
        $this->assertNull($deletedModel);
        $this->assertEquals(0, count(SampleOnlyModel::exportAll($this->uid)));
    }

    public function testGetOrCreate() {
        $model = SampleOnlyModel::getOrCreate(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female',
        ), $this->uid, $this->pid);
        $this->assertInstanceOf('SampleOnlyModel', $model);
        $this->assertInstanceOf('\Model\Base\ActiveRecord', $model);
        $this->assertEquals($model->title, $this->title);
        $model1 = SampleOnlyModel::getOrCreate(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female',
        ), $this->uid, $this->pid);
        $this->assertSame($model, $model1);
    }

    public function testGetDeleteGetAll() {
        $model = SampleOnlyModel::create(array(
            'uid' => $this->uid,
            'pid' => $this->pid,
            'title' => $this->title,
            'gendar' => 'female',
        ));
        $this->assertInstanceOf('SampleOnlyModel', $model);
        $model->delete();
        $models = SampleOnlyModel::getAll($this->uid);
        $this->assertEquals(0, count($models));
    }

    public function testEmptyGetAllOnlyOneDbQuery() {
        $jobs = \Model\JobModel::getAll(1);
        $this->assertEquals(0, count($jobs)); // empty result
        $linesOfDbQuery = $this->lineCountOfFile('/var/log/dw/dbquery.json');

        // getAll again
        \Model\JobModel::getAll(1);

        // assert no extra db query
        $this->assertEquals($linesOfDbQuery,
            $this->lineCountOfFile('/var/log/dw/dbquery.json'));
    }

    public function testGetSetColumnHook() {
        $this->prepareSampleRow();
        $model = SampleOnlyModel::get($this->uid, $this->pid); // uid, pid
        $model->gendar = "female"; // will trigger set_gendar()
        $this->assertEquals('I am female', $model->gendar); // will trigger get_gendar()
    }

}
