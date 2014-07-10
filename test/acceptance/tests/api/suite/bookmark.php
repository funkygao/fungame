<?php

login_with_deviceid($I, $myId);

// add bookmark
$ret = call_commit($I, 'Player', 'addBookmark', array(
    'uid' => $uid,
    'kingdom_id' => 1,
    'map_x' => 22,
    'map_y' => 33,
    'name' => 'test',
    'type' => 1,
    'favourite' => 1,
));
$I->assertTrue($ret['payload']['ret']);

// modify bookmark
$ret = call_commit($I, 'Player', 'modifyBookmark', array(
    'uid' => $uid,
    'kingdom_id' => 1,
    'map_x' => 22,
    'map_y' => 33,
    'name' => 'ff',
    'type' => 2,
    'favourite' => 0,
));
$I->assertTrue($ret['payload']['ret']);

// get bookmark
$ret = call_commit($I, 'Player', 'getAllBookmark', array(
    'uid' => $uid,
));
$I->assertGreaterThenOrEqual(1, $ret['payload']['list']);

// delete bookmark
$ret = call_commit($I, 'Player', 'deleteBookmark', array(
    'uid' => $uid,
    'kingdom_id' => 1,
    'map_x' => 22,
    'map_y' => 33,
));
$I->assertTrue($ret['payload']['ret']);
