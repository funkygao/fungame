<?php

// upgrade StrongHold
$strongholdLevel1 = $user_data['payload']['city']['tiles'][0]['level'];
call_commit($I, 'city', 'upgradeObject', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'instant' => TRUE,
    'building_id' => 1,
    'map_x' => 11,
    'map_y' => -33, // the StrongHold (x,y) is always (11, -33)
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));
// assert level is up
list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);
$strongholdLevel2 = $user_data['payload']['city']['tiles'][0]['level'];
$I->assertEquals($strongholdLevel1 + 1, $strongholdLevel2);

// assert resources are deducted

// assert gold is deducted because we used 'instant'
$gold = $user_data['payload']['user']['info']['gold'];
$I->assertGreaterThen($gold, 100000);
$building_id = mt_rand();

$buildingConfig = \System\GameData::getInstance('Building');
$line = $buildingConfig->getById('BARRACKS_0');
// add a new building
call_commit($I, 'city', 'addObject', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'building_id' => $building_id,
    'instant' => TRUE,
    'map_x' => 4435,
    'map_y' => 911,
    'type' => $line['Internal_ID'], // training grounds
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));
list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);
$I->assertEquals(4, count($user_data['payload']['city']['tiles']));

// train troop
$infantryT1Old = $user_data['payload']['city']['info']['troop']['INFANTRY_T1'];
call_commit($I, 'city', 'trainTroop', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'instant' => TRUE,
    'map_x' => 4435,
    'map_y' => 911,
    'building_id' => $building_id,
    'class' => 'INFANTRY_T1',
    'count' => 7,
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));
list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);
$infantryT1New = $user_data['payload']['city']['info']['troop']['INFANTRY_T1'];
$I->assertEquals($infantryT1Old + 7, $infantryT1New);

// deconstruct
call_commit($I, 'city', 'deconstructObject', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'instant' => TRUE,
    'map_x' => 4435,
    'map_y' => 911,
    'building_id' => $building_id,
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));
list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);
$I->assertEquals(3, count($user_data['payload']['city']['tiles']));
