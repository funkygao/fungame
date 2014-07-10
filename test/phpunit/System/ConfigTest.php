<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class ConfigTest extends FunTestCaseBase {

    public function testGet() {
        $this->assertEquals('mysql', \System\Config::get('database', 'tools.type'));
        $this->assertEquals(NULL, \System\Config::get('database', 'tools.non_exists'));
    }

    public function testInvalidConfig() {
        $this->setExpectedException('\InvalidArgumentException');
        \System\Config::get('non-exist', 'foo.bar');
    }

    public function testIsDebugMode() {
        $this->assertTrue(\System\Config::isDebugMode());
    }

    public function testPartialPath() {
        $server = \System\Config::get('beanstalkd', 'default');
        $this->assertEquals('127.0.0.1', $server['host']);
    }

    public function testEmptyKeyPath() {
        $loggers = \System\Config::get('log');
        $this->assertEquals('file:/var/log/dw/', $loggers[0]);
    }

    public function testCacheRelated() {
        $pool = \System\Config::get('cache', 'default');
        $this->assertEquals(2, count($pool['servers']));
    }

}
