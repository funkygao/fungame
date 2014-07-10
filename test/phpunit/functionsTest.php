<?php

require_once "FunTestCaseBase.php";

class functionsTest extends FunTestCaseBase {

    public function tearDown() {
        // to avoid Flusher flush
    }

    public function testIsAssoc() {
        $this->assertFalse(is_assoc(array(1, 3, 5)));
        $this->assertTrue(is_assoc(array(
            '1' => array(
                5, 6,
            )
        )));
        $this->assertFalse(is_assoc(array('1', '2')));
    }

    public function testStringRelated() {
        $this->assertTrue(str_startswith('abcde', 'a'));
        $this->assertTrue(str_startswith('abcde', 'ab'));
        $this->assertTrue(str_endswith('abced', 'd'));
        $this->assertTrue(str_endswith('abced', 'ed'));
        $this->assertTrue(str_startswith(' xx', ' '));
        $this->assertTrue(str_contains('abcd', 'b'));
        $this->assertTrue(str_contains('abcd', 'bc'));
        $this->assertFalse(str_contains('cdf', 'a'));
        $this->assertTrue(str_icontains('abc', 'A'));
        $this->assertFalse(str_icontains('abc', 'd'));
    }

    public function testArrayDeepSetGet() {
        $arr = array(
            'limit' => array(
                'wood' => array(
                    'base' => 10,
                    'boost' => 1.2,
                ),
            ),
        );

        // not found with default value
        $this->assertEquals('shit', array_deep_get($arr, 'non-exist', 'shit'));
        $this->assertEquals(NULL, array_deep_get($arr, 'limit.silver.base'));

        // successful get
        $this->assertEquals(1.2, array_deep_get($arr, 'limit.wood.boost'));
        $this->assertEquals(10, array_deep_get($arr, 'limit.wood.base'));

        // deep set, then deep get to check whether it was set successfully
        array_deep_set($arr, 'limit.wood.base', 139);
        $this->assertEquals(139, array_deep_get($arr, 'limit.wood.base'));

        array_deep_del($arr, 'limit.wood.base');
        $this->assertTrue(empty($arr['limit']['wood']['base']));
        $this->assertFalse(empty($arr['limit']['wood']['boost']));
        array_deep_del($arr, 'limit.wood');
        $this->assertTrue(empty($arr['limit']));
    }

    public function testArrayDeepSetForEmptyArray() {
        $arr = array();
        array_deep_set($arr, 'a.b.c.', 1); // last char is '.', will be omitted
        $this->assertEquals(1, array_deep_get($arr, 'a.b.c'));
        $this->assertEquals(array(
            'a' => array(
                'b' => array(
                    'c' => 1,
                )
            )
        ), $arr);
    }

    public function testRequestContext() {
        $ctx0 = request_ctx();
        $ctx1 = request_ctx();
        $this->assertEquals($ctx0['seq'] + 1, $ctx1['seq']);
        $this->assertGreaterThanOrEqual($ctx0['elapsed'], $ctx1['elapsed']);
    }

    public function testInBetween() {
        $this->assertTrue(in_between(1, 10, 4));
        $this->assertTrue(in_between(1, 10, 1));
        $this->assertTrue(in_between(1, 10, 10));
        $this->assertFalse(in_between(1, 10, 11));
    }

    public function testDuration() {
        $this->assertEquals(78.10249675906654, movement_duration(1, 10, 5, 88, 1.0));
        $this->assertEquals(52.068331172711027, movement_duration(1, 10, 5, 88, 1.5));
        $this->assertLessThan(movement_duration(1, 10, 5, 88, 1.1),
            movement_duration(1, 10, 5, 88, 1.2)); // bigger speed, less duration
    }

    public function testDistance() {
        $this->assertEquals(78.10249675906654, distance(1, 10, 5, 88));
    }

    public function testTimestampConvertion() {
        $now = 1401604247;
        $this->assertEquals('2014-06-01 14:30:47', ts_unix2mysql($now));
        $this->assertEquals($now, ts_mysql2unix('2014-06-01 14:30:47'));
        $this->assertEquals('1970-01-01 08:00:00', ts_unix2mysql(NULL));
        $this->assertEquals('1970-01-01 08:00:00', ts_unix2mysql(0));
    }

    public function testGetClassBasename() {
        $userInfo = \Model\UserInfoModel::instance(array(
            'uid' => 1,
            'name' => 'hello'
        ));
        $this->assertInstanceOf('\Model\UserInfoModel', $userInfo);
        $this->assertEquals('UserInfoModel', get_class_basename($userInfo));
        $this->assertEquals(NULL, get_class_basename("adf"));

        $class = '\Manager\March\Battle';
        $march = new $class();
        $this->assertInstanceOf('\Manager\March\Battle', $march);
        $this->assertEquals('Battle', get_class_basename($march));
    }

    public function testStrPadLeadingZero() {
        $val = 8;
        $this->assertEquals('08', num_zero_fill($val));
        $val = 80;
        $this->assertEquals('80', num_zero_fill($val));

        $val = 180;
        $this->assertEquals('180', num_zero_fill($val));
    }
}
