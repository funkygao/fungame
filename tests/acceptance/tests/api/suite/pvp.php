<?php

// start PVP march
$marchInfo = call_commit($I, 'PVP', 'startMarch', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'instant' => TRUE,
    'x' => 1,
    'y' => 1,
    'troops' => array(
        'INFANTRY_T1' => 20,
        'CAVALRY_T1' => 100,
    ),
));
$marchId = $marchInfo['payload']['march_id'];
$I->assertGreaterThenOrEqual(1, $marchId);
$I->assertGreaterThenOrEqual(1, $marchInfo['payload']['job_id']);
$I->assertEquals(20, $marchInfo['payload']['troops']['INFANTRY_T1']);


// load PVP march
$marchInfo = call_commit($I, 'PVP', 'loadMarch', array(
    'uid' => $uid,
    'march_id' => $marchId,
));
$I->assertGreaterThenOrEqual(1, $marchInfo['payload']['march_id']);


// recall PVP march
call_commit($I, 'PVP', 'recallMarch', array(
    'uid' => $uid,
    'march_id' => $marchId,
));
$I->assertGreaterThenOrEqual(1, $marchInfo['payload']['march_id']);
