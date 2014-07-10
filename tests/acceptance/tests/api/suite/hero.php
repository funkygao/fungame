<?php

$equipmentConfig = \System\GameData::getInstance('Hero_Equipment');
$line = $equipmentConfig->getById('EQUIP_HEAD_TEMP1_White');
// Hero set head
$ret = call_commit($I, 'Hero', 'setHead', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'head_item' => $line['Internal_ID'],
));
$I->assertEquals($line['Internal_ID'], $ret['payload']['head_item']);

// Hero add equipment
/*
call_commit($I, 'Hero', 'swapHeroEquipment', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'item_id' => 5,
    'bodyPos' => 'helm_item',
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));

// Hero destroy equipment
call_commit($I, 'Item', 'removeInventoryItem', array(
    'uid' => $uid,
    'item_id' => 11,
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));
*/