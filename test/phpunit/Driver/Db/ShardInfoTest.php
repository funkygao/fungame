<?php

require_once realpath(__DIR__ . '/../../') . "/FunTestCaseBase.php";

class ShardInfoTest extends FunTestCaseBase {

    public function testUserShardInfo() {
        $info = \Driver\Db\ShardInfo::factory('user', 10);
        $this->assertEquals('UserShard10', $info->getDatabaseName());
        $this->assertEquals('user', $info->getPool());
    }

    public function testToolsShardInfo() {
        $info = \Driver\Db\ShardInfo::factory('tools', 10);
        $this->assertEquals('tools', $info->getDatabaseName());
    }

    public function testShardLookupShardInfo() {
        $info = \Driver\Db\ShardInfo::factory('lookup', 10);
        $this->assertEquals('ShardLookup', $info->getDatabaseName());
    }

    public function testAllianceShardInfo() {
        $info = \Driver\Db\ShardInfo::factory('alliance', 5);
        $this->assertEquals('AllianceShard5', $info->getDatabaseName());
    }

    public function testWorldShardInfo() {
        $info = \Driver\Db\ShardInfo::factory('world', 2);
        $this->assertEquals('WorldShard2', $info->getDatabaseName());
    }

    public function testTicketsShardInfo() {
        $info = \Driver\Db\ShardInfo::factory('tickets', 200);
        $this->assertEquals('Tickets', $info->getDatabaseName());
    }

}
