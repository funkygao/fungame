<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class CacheFactoryTest extends FunTestCaseBase {

    public function testDumbSetAndGet() {
        $this->markTestSkipped('When we start on cache, turn this test on');

        $pool = 'default';
        $key = 'foo';
        $val = 'bar';
        $this->assertEquals(false, \Driver\CacheFactory::instance($pool)->set($key, $val));
        $this->assertEquals(false, \Driver\CacheFactory::instance($pool)->get($key));
    }

    public function testMemcacheSetAndGet() {
        $this->markTestSkipped('When we start on cache, turn this test on');

        $pool = 'some-other';
        $key = 'foo';
        $val = 'bar';
        $this->assertEquals(true, \Driver\CacheFactory::instance($pool)->set($key, $val));
        $this->assertEquals($val, \Driver\CacheFactory::instance($pool)->get($key));
    }
}
