<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class GameDataTest extends FunTestCaseBase {

    /**
     * @var \System\GameData
     */
    private $config;

    protected function setUp() {
        $this->config = \System\GameData::getInstance('Building', __DIR__ . '/fixtures/');
    }

    public function testGetByPk() {
        $internalId = 2;
        $row = $this->config->getByPk($internalId);
        $this->assertEquals($row['Internal_ID'], $internalId);
        $this->assertGreaterThanOrEqual(1, count($row['Requirements']));
        $this->assertEquals(200, $row['Costs']['Wood']);

        // non-exist internal id will return NULL
        $this->assertNull($this->config->getByPk(78987));
    }

    public function testGetById() {
        $row = $this->config->getById('FARM_2');
        $this->assertEquals($row['Costs']['Wood'], 400);
        $this->assertGreaterThanOrEqual(count($row['Requirements']), 1);
    }

    public function testInquire() {
        // inquire by internal id
        $p = $this->config->inquire('2');
        $this->assertTrue(isset($p['Costs']));
        $this->assertInternalType('array', $p['Costs']);

        // inquire internalId.Requirements
        $p = $this->config->inquire('2.Requirements');
        $this->assertEquals(2, count($p));

    }

    public function testFindByCriteria() {
        $lines = $this->config->findByCriteria(array(
                'Building_ID' => 'HOUSE',
                'Requirements' => 96, // operator is 'equals' by default
            )
        );
        $this->assertEquals(count($lines), 1);
        $this->assertEquals(current($lines)['ID'], 'HOUSE_2');

        $lines = $this->config->findByCriteria(array(
                'Building_Lvl' => array('<' => '1'),
            )
        );
        $this->assertEquals(count($lines), 23);
        foreach($lines as $row){
            $this->assertLessThan(1, $row['Building_Lvl']);
        }
    }

    public function testInternalIdToSheetName() {
        $sheetName = \System\GameData::internalIdToSheetName(200000);
        $this->assertEquals('Building', $sheetName);

        $sheetName = \System\GameData::internalIdToSheetName(200001);
        $this->assertEquals('Building', $sheetName);

        $sheetName = \System\GameData::internalIdToSheetName(199999);
        $this->assertEquals('skill_statistics', $sheetName);
    }

}
