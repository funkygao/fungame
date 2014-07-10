<?php

// start PVE march
$marchInfo = call_commit($I, 'PVE', 'startMarch', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'instant' => TRUE,
    'encounter_seq' => 1,
    'zone_id' => 1,
    'troops' => array(
        'INFANTRY_T1' => 60,
    ),
));
$marchId = $marchInfo['payload']['march']['march_id'];
$I->assertGreaterThenOrEqual(1, $marchId);
$I->assertGreaterThenOrEqual(1, $marchInfo['payload']['march']['job_id']);
$I->assertEquals(array('INFANTRY_T1' => 60), $marchInfo['payload']['march']['start_troops']);


// load PVE march
$marchInfo = call_commit($I, 'PVE', 'loadMarch', array(
    'uid' => $uid,
    'march_id' => $marchId,
));
$I->assertGreaterThenOrEqual(1, count($marchInfo['payload']['zone']));
$I->assertGreaterThenOrEqual(1, $marchInfo['payload']['march']['march_id']);


// recall PVE march
call_commit($I, 'PVE', 'recallMarch', array(
    'uid' => $uid,
    'march_id' => $marchId,
));
$I->assertGreaterThenOrEqual(1, $marchInfo['payload']['march']['march_id']);
